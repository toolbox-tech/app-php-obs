<?php
include $_SERVER['DOCUMENT_ROOT'] . '/include/config.inc.php';

use Rakit\Validation\Validator;

$validator = new Validator;

include 'conexao.php';

if (!empty($_SESSION['usuario'])) {
    $usuario = $_SESSION['usuario'];
} else {
    header('Location: /');
}

if (empty($_POST)) {
    $_POST = json_decode(file_get_contents("php://input"), true);
}

function setResponseCode($code, $reason = null)
{
    $code = intval($code);

    if (version_compare(phpversion(), '5.4', '>') && is_null($reason))
        http_response_code($code);
    else
        header(trim("HTTP/1.0 $code $reason"));
}


switch ($_POST['enviar']) {

    case 'percurso_retornou':
        $id = $_POST['id'];
        $odo_retorno = $_POST['odo_retorno'];

        $validation = $validator->make($_POST + $_FILES, [
            'id'                  => 'required|integer',
            'odo_retorno'      => 'required|numeric',
        ]);

        // then validate
        $validation->validate();

        if ($validation->fails()) {
            setResponseCode(400, "Dados Inválidos");
        } else {

            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM percursos WHERE id_percurso = ? AND status IS NULL");
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                setResponseCode(400, $e);
            }

            $resultado = $stmt->fetch();

            if ($resultado['total'] > 0) {

                try {
                    $stmt = $pdo->prepare("SELECT odo_saida FROM percursos WHERE id_percurso = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $stmt->execute();
                } catch (PDOException $e) {
                    setResponseCode(400, $e);
                    $_SESSION['POST'] = true;
                }

                $resultado = $stmt->fetch();

                if ($resultado['odo_saida'] > $odo_retorno) {
                    setResponseCode(400, "Dados Inválidos");
                    $_SESSION['POST'] = true;
                } else {

                    try {
                        $stmt = $pdo->prepare("UPDATE percursos 
                                                SET odo_retorno= ?, data_retorno=NOW(), hora_retorno=NOW(),  id_usuario_retorno = ?, status = 1
                                                WHERE id_percurso= ?");
                        $stmt->bindParam(1, $odo_retorno, PDO::PARAM_STR);
                        $stmt->bindParam(2, $usuario, PDO::PARAM_INT);
                        $stmt->bindParam(3, $id, PDO::PARAM_INT);
                        $executa = $stmt->execute();

                        if (!$executa) {
                            print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
                        } else {
                            $_SESSION['atualizado'] = 1;
                        }
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                }
            } else {
                setResponseCode(400, $e);
            }
        }

        header('Location: /percurso');

        break;

    case 'percurso_retornou_guarda':

        $id = $_POST['id'];
        $odo_retorno = $_POST['odo_retorno'];

        $validation = $validator->make($_POST + $_FILES, [
            'id'                  => 'required|integer',
            'odo_retorno'      => 'required|numeric',
            'usuario'                  => 'required',
            'senha'                  => 'required',
        ]);

        // then validate
        $validation->validate();

        if ($validation->fails()) {
            setResponseCode(400, "Dados Inválidos");
            $_SESSION['POST'] = true;
        } else {

            $login = $_POST['usuario'];
            $senha = md5($_POST['senha']);

            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) AS total 
                                                FROM usuarios 
                                                WHERE login = ? 
                                                AND senha = ?
                                                AND id_status != 2');
                $stmt->bindParam(1, $login, PDO::PARAM_STR);
                $stmt->bindParam(2, $senha, PDO::PARAM_STR);
                $stmt->execute();

                $resultado = $stmt->fetch();

                $stmt = $pdo->prepare('SELECT id_perfil, nome, id_usuario
                                                FROM usuarios 
                                                WHERE login = ?');
                $stmt->bindParam(1, $login, PDO::PARAM_STR);
                $stmt->execute();
            } catch (PDOException $e) {
                setResponseCode(400, $e);
                $_SESSION['POST'] = true;
            }

            $resultado1 = $stmt->fetch();

            if ($resultado['total'] > 0) {

                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM percursos WHERE id_percurso = ? AND status IS NULL");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $stmt->execute();
                } catch (PDOException $e) {
                    setResponseCode(400, $e);
                }

                $resultado = $stmt->fetch();

                if ($resultado['total'] > 0) {

                    try {
                        $stmt = $pdo->prepare("SELECT odo_saida FROM percursos WHERE id_percurso = ?");
                        $stmt->bindParam(1, $id, PDO::PARAM_INT);
                        $stmt->execute();
                    } catch (PDOException $e) {
                        setResponseCode(400, $e);
                        $_SESSION['POST'] = true;
                    }

                    $resultado = $stmt->fetch();

                    if ($resultado['odo_saida'] > $odo_retorno) {
                        setResponseCode(400, "Dados Inválidos");
                        $_SESSION['POST'] = true;
                    } else {

                        $stmt = $pdo->prepare("UPDATE percursos 
                                                        SET odo_retorno= ?, data_retorno=NOW(), hora_retorno=NOW(),  id_usuario_retorno = ?, status = 1
                                                        WHERE id_percurso= ?");
                        $stmt->bindParam(1, $odo_retorno, PDO::PARAM_STR);
                        $stmt->bindParam(2, $resultado1[2], PDO::PARAM_INT);
                        $stmt->bindParam(3, $id, PDO::PARAM_INT);
                        $executa = $stmt->execute();

                        if (!$executa) {
                            print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                    <strong>Não foi possível acessar a base de dados</strong>
                                    </div>");
                        } else {
                            $_SESSION['atualizado'] = 1;
                        }
                    }
                } else {
                    $_SESSION['erro2'] = 1;
                    header('Location: /');
                }
            } else {
                $_SESSION['erro'] = 1;
                header('Location: /');
            }
        }

        header('Location: /percurso_guarda');

        break;


    case 'Apagar_guarda':

        $login = $_POST['usuario'];
        $senha = md5($_POST['senha']);

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total 
                                            FROM usuarios 
                                            WHERE login = ? 
                                            AND senha = ?
                                            AND id_status != 2');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->bindParam(2, $senha, PDO::PARAM_STR);
            $stmt->execute();

            $resultado = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT id_perfil, nome, id_usuario
                                            FROM usuarios 
                                            WHERE login = ?');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->execute();

            $resultado1 = $stmt->fetch();

            if ($resultado['total'] > 0) {
                $id = $_POST['id'];
                $motivo = $_POST['motivo_apagado'];

                $stmt = $pdo->prepare("UPDATE percursos
                                        SET status = 2, motivo_apagado = ?, id_usuario_retorno = ?
                                        WHERE id_percurso= ?");
                $stmt->bindParam(1, $motivo, PDO::PARAM_STR);
                $stmt->bindParam(2, $resultado1[2], PDO::PARAM_INT);
                $stmt->bindParam(3, $id, PDO::PARAM_INT);
                $executa = $stmt->execute();

                if (!$executa) {
                    print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                <strong>Não foi possível acessar a base de dados</strong>
                            </div>");
                } else {
                    $_SESSION['apagado'] = 1;
                }
            } else {
                $_SESSION['erro3'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /percurso_guarda');

        break;

    case 'Apagar':
        $id = $_POST['id'];
        $motivo = $_POST['motivo_apagado'];

        try {
            $stmt = $pdo->prepare("UPDATE percursos
                                    SET status = 2, motivo_apagado = ?, id_usuario_retorno = ?
                                    WHERE id_percurso= ?");
            $stmt->bindParam(1, $motivo, PDO::PARAM_STR);
            $stmt->bindParam(2, $usuario, PDO::PARAM_INT);
            $stmt->bindParam(3, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                            </div>");
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /percurso');

        break;

    case 'percurso':

        $viatura = $_POST["viatura"];
        $nome = $_POST["motorista"];
        $destino = ucwords(strtolower($_POST["destino"]));
        $odometro = $_POST["odo_saida"];

        if (empty($_POST["acompanhante"])) {
            $acompanhante = NULL;
        } else {
            $acompanhante = ucwords(strtolower($_POST["acompanhante"]));
        }
        try {
            $stmt = $pdo->prepare("SELECT COUNT(nome_destino) AS existente
                                                FROM destinos
                                                WHERE nome_destino = ?");
            $stmt->bindParam(1, $destino, PDO::PARAM_STR);
            $executa = $stmt->execute();

            $reg = $stmt->fetch(PDO::FETCH_OBJ);
            if ($reg->existente < 1) {

                $stmt = $pdo->prepare("INSERT INTO destinos
                                                   VALUES(NULL,?)");
                $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                $executa = $stmt->execute();

                $stmt = $pdo->prepare("SELECT id_destino
                                                    FROM destinos
                                                    WHERE nome_destino = ?");
                $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                $executa = $stmt->execute();
                $reg = $stmt->fetch(PDO::FETCH_OBJ);
                $destino = $reg->id_destino;
            } else {

                $stmt = $pdo->prepare("SELECT id_destino
                                                                FROM destinos
                                                                WHERE nome_destino = ?");
                $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                $executa = $stmt->execute();
                $reg = $stmt->fetch(PDO::FETCH_OBJ);
                $destino = $reg->id_destino;
            }

            $stmt = $pdo->prepare("INSERT INTO percursos
                                                VALUES(NULL,?,?,?,?,?,CURDATE(),CURRENT_TIME(),NULL,NULL,NULL,?,NULL,NULL,NULL)");
            $stmt->bindParam(1, $viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $nome, PDO::PARAM_INT);
            $stmt->bindParam(3, $destino, PDO::PARAM_INT);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $acompanhante, PDO::PARAM_STR);
            $stmt->bindParam(6, $usuario, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /percurso');

        break;

    case 'percurso_guarda':

        // make it
        $validation = $validator->make($_POST + $_FILES, [
            'viatura'                  => 'required|integer',
            'motorista'                 => 'required|integer',
            'destino'              => 'required',
            'odo_saida'      => 'required',
            'usuario'                => 'required',
            'senha'                => 'required',
        ]);

        // then validate
        $validation->validate();

        if ($validation->fails()) {
            $_SESSION['POST'] = serialize($_POST);
            header('Location: /percurso_guarda');
            // handling errors
            /* $errors = $validation->errors();
            echo "<pre>";
            print_r($errors->firstOfAll());
            echo "</pre>"; */
            exit;
        } else {
            // validation passes
            echo "Success!";
        }

        $login = $_POST['usuario'];
        $senha = md5($_POST['senha']);

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total 
                                                FROM usuarios 
                                                WHERE login = ? 
                                                AND senha = ?
                                                AND id_status != 2');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->bindParam(2, $senha, PDO::PARAM_STR);
            $stmt->execute();

            $resultado = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT id_perfil, nome, id_usuario
                                                FROM usuarios 
                                                WHERE login = ?');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->execute();

            $resultado1 = $stmt->fetch();

            if ($resultado['total'] > 0) {

                $viatura = $_POST["viatura"];
                $nome = $_POST["motorista"];
                $destino = ucwords(strtolower($_POST["destino"]));
                $odometro = $_POST["odo_saida"];

                if (empty($_POST["acompanhante"])) {
                    $acompanhante = NULL;
                } else {
                    $acompanhante = ucwords(strtolower($_POST["acompanhante"]));
                }
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(nome_destino) AS existente
                                                        FROM destinos
                                                        WHERE nome_destino = ?");
                    $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                    $executa = $stmt->execute();

                    $reg = $stmt->fetch(PDO::FETCH_OBJ);
                    if ($reg->existente < 1) {

                        $stmt = $pdo->prepare("INSERT INTO destinos
                                                           VALUES(NULL,?)");
                        $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                        $executa = $stmt->execute();

                        $stmt = $pdo->prepare("SELECT id_destino
                                                            FROM destinos
                                                            WHERE nome_destino = ?");
                        $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                        $executa = $stmt->execute();
                        $reg = $stmt->fetch(PDO::FETCH_OBJ);
                        $destino = $reg->id_destino;
                    } else {

                        $stmt = $pdo->prepare("SELECT id_destino
                                                                        FROM destinos
                                                                        WHERE nome_destino = ?");
                        $stmt->bindParam(1, $destino, PDO::PARAM_STR);
                        $executa = $stmt->execute();
                        $reg = $stmt->fetch(PDO::FETCH_OBJ);
                        $destino = $reg->id_destino;
                    }

                    $stmt = $pdo->prepare("INSERT INTO percursos
                                                        VALUES(NULL,?,?,?,?,?,CURDATE(),CURRENT_TIME(),NULL,NULL,NULL,?,NULL,NULL,NULL)");
                    $stmt->bindParam(1, $viatura, PDO::PARAM_INT);
                    $stmt->bindParam(2, $nome, PDO::PARAM_INT);
                    $stmt->bindParam(3, $destino, PDO::PARAM_INT);
                    $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
                    $stmt->bindParam(5, $acompanhante, PDO::PARAM_STR);
                    $stmt->bindParam(6, $resultado1[2], PDO::PARAM_INT);
                    $executa = $stmt->execute();

                    if (!$executa) {
                        print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                    <strong>Não foi possível acessar a base de dados</strong>
                                 </div>");
                        http_response_code(400);
                    } else {
                        $_SESSION['cadastrado'] = 1;
                        http_response_code(200);
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['erro1'] = 1;
                header('Location: /');
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /percurso_guarda');

        break;

    case 'viatura':
        $marca = $_POST["marca"];
        $modelo = $_POST["modelo"];
        $placa = mb_strtoupper($_POST["placa"]);
        $odometro = $_POST["odometro"];
        $ano = $_POST["ano"];
        $tipo_viatura = $_POST["tipo_viatura"];
        $situacao = $_POST["situacao"];
        $habilitacao = $_POST["habilitacao"];
        $combustivel = $_POST["combustivel"];
        $rfid = $_POST["rfid__"];

        try {
            $stmt = $pdo->prepare("INSERT INTO viaturas (id_viatura, id_marca, id_modelo, placa, odometro, ano, 
            id_tipo_viatura, id_situacao, id_usuario,
             id_habilitacao, id_combustivel, rfid, id_status) 
            VALUES(NULL,?,?,?,?,?,?,?,?,?,?,?,1)");
            $stmt->bindParam(1, $marca, PDO::PARAM_INT);
            $stmt->bindParam(2, $modelo, PDO::PARAM_INT);
            $stmt->bindParam(3, $placa, PDO::PARAM_STR);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $ano, PDO::PARAM_INT);
            $stmt->bindParam(6, $tipo_viatura, PDO::PARAM_INT);
            $stmt->bindParam(7, $situacao, PDO::PARAM_INT);
            $stmt->bindParam(8, $usuario, PDO::PARAM_INT);
            $stmt->bindParam(9, $habilitacao, PDO::PARAM_INT);
            $stmt->bindParam(10, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(11, $rfid, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /viaturascadastradas');

        break;

    case 'apagar_viatura':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM viaturas
                                                WHERE id_viatura =" . $id);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE viaturas
                                                SET id_status = 2
                                                WHERE id_viatura = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                    if (!$executa) {
                        $_SESSION['erro'] = 1;
                    } else {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /viaturascadastradas');

        break;

    case 'atualizar_viatura':

        $id = $_POST['id'];
        $marca = $_POST["marca"];
        $modelo = $_POST["modelo"];
        $placa = mb_strtoupper($_POST["placa"]);
        $odometro = $_POST["odometro"];
        $ano = $_POST["ano"];
        $situacao = $_POST["situacao"];
        $tipo_viatura = $_POST["tipo_viatura"];
        $habilitacao = $_POST["habilitacao"];
        $combustivel = $_POST["combustivel"];
        $rfid = $_POST["rfid__"];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));
        $motivo = $_POST["motivo"];



        try {
            $stmt = $pdo->prepare("UPDATE viaturas
                                                SET id_marca = ?, id_modelo = ?, placa = ?, odometro = ?, 
                                                ano = ?,   id_tipo_viatura =?, id_situacao = ?, 
                                                id_habilitacao = ?, id_combustivel = ?, rfid = ?
                                                WHERE id_viatura = ?");
            $stmt->bindParam(1, $marca, PDO::PARAM_INT);
            $stmt->bindParam(2, $modelo, PDO::PARAM_INT);
            $stmt->bindParam(3, $placa, PDO::PARAM_STR);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $ano, PDO::PARAM_INT);
            $stmt->bindParam(6, $tipo_viatura, PDO::PARAM_INT);
            $stmt->bindParam(7, $situacao, PDO::PARAM_INT);
            $stmt->bindParam(8, $habilitacao, PDO::PARAM_INT);
            $stmt->bindParam(9, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(10, $rfid, PDO::PARAM_STR);
            $stmt->bindParam(11, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        /* if ($situacao == 2) {
            try {
                $stmt = $pdo->prepare("INSERT INTO indisponibilidade VALUES (NULL, ?, ?, ?, ?, 1, $usuario)");
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $stmt->bindParam(2, $motivo, PDO::PARAM_STR);
                $stmt->bindParam(3, $data, PDO::PARAM_STR);
                $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
                $executa = $stmt->execute();

                if (!$executa) {
                    $_SESSION['erro'] = 1;
                } else {
                    $_SESSION['atualizado'] = 1;
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }

        if ($situacao == 1) {
            try {
                $stmt = $pdo->prepare("UPDATE indisponibilidade SET id_status = 2 WHERE id_viatura = ?");
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $executa = $stmt->execute();

                if (!$executa) {
                    $_SESSION['erro'] = 1;
                } else {
                    $_SESSION['atualizado'] = 1;
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        } */

        header('Location: /viaturascadastradas');

        break;

    case 'motorista':
        $id_militar = $_POST['militar'];
        $categoria = $_POST['categoria'];
        $cnh = $_POST['cnh'];
        $validade = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['validade'])));

        try {

            $stmt = $pdo->prepare("SELECT sigla, nome
                                                FROM posto_grad, militares
                                                WHERE posto_grad.id_posto_grad = militares.id_posto_grad
                                                AND militares.id_militar = ?");
            $stmt->bindParam(1, $id_militar, PDO::PARAM_INT);
            $executa = $stmt->execute();
            $dados_motoristas = $stmt->fetch(PDO::FETCH_OBJ);
            $sigla = $dados_motoristas->sigla;
            $nome = $dados_motoristas->nome;

            $apelido = $sigla . " " . $nome;

            $stmt = $pdo->prepare("INSERT INTO motoristas
                                                VALUES(NULL,?,?,?,?,?,$usuario,1)");
            $stmt->bindParam(1, $id_militar, PDO::PARAM_INT);
            $stmt->bindParam(2, $categoria, PDO::PARAM_INT);
            $stmt->bindParam(3, $cnh, PDO::PARAM_STR);
            $stmt->bindParam(4, $validade, PDO::PARAM_STR);
            $stmt->bindParam(5, $apelido, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /motoristascadastrados');

        break;

    case 'atualizar_motorista':
        $id = $_POST['id'];
        $id_militar = $_POST['militar'];
        $categoria = $_POST['categoria'];
        $cnh = $_POST['cnh'];
        $validade = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['validade'])));


        try {

            $stmt = $pdo->prepare("SELECT sigla, nome
                                                FROM posto_grad, militares
                                                WHERE posto_grad.id_posto_grad = militares.id_posto_grad
                                                AND militares.id_militar = ?");
            $stmt->bindParam(1, $id_militar, PDO::PARAM_INT);
            $executa = $stmt->execute();
            $dados_motoristas = $stmt->fetch(PDO::FETCH_OBJ);
            $sigla = $dados_motoristas->sigla;
            $nome = $dados_motoristas->nome;

            $apelido = $sigla . " " . $nome;

            $stmt = $pdo->prepare("UPDATE motoristas
                                                SET id_militar = ?, id_habilitacao = ?, cnh = ?, validade = ?, apelido = ?
                                                WHERE id_motorista = ?");
            $stmt->bindParam(1, $id_militar, PDO::PARAM_INT);
            $stmt->bindParam(2, $categoria, PDO::PARAM_INT);
            $stmt->bindParam(3, $cnh, PDO::PARAM_STR);
            $stmt->bindParam(4, $validade, PDO::PARAM_STR);
            $stmt->bindParam(5, $apelido, PDO::PARAM_STR);
            $stmt->bindParam(6, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /motoristascadastrados');

        break;


    case 'Apagar Motorista':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM motoristas
                                                WHERE id_motorista= ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE motoristas
                                                SET id_status = 2
                                                WHERE id_motorista = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    } else {
                        $_SESSION['erro'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /motoristascadastrados');

        break;

    case 'ativar_motorista':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("UPDATE motoristas
                                                SET id_status = 1
                                                WHERE id_motorista = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();
            if ($executa) {
                $_SESSION['ativado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /motoristascadastrados');

        break;

    case 'cadastrar_usuario':
        $militar = $_POST['militar'];
        $login = $_POST['login'];
        $senha = md5($_POST['senha']);
        $perfil = $_POST['perfil'];
        $apelido = ucwords(strtolower($_POST['apelido']));

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios 
                                                VALUES(NULL,?,?,?,?,?,1,?)");
            $stmt->bindParam(1, $militar, PDO::PARAM_INT);
            $stmt->bindParam(2, $login, PDO::PARAM_STR);
            $stmt->bindParam(3, $senha, PDO::PARAM_STR);
            $stmt->bindParam(4, $perfil, PDO::PARAM_INT);
            $stmt->bindParam(5, $apelido, PDO::PARAM_STR);
            $stmt->bindParam(6, $usuario, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['cadastrado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        if (!$executa) {
            $_SESSION['erro'] = 1;
        } else {
            $_SESSION['cadastrado'] = 1;
        }

        header('Location: /usuarioscadastrados');

        break;

    case 'apagar_usuario':
        $id = $_POST['id'];

        try {


            $stmt = $pdo->prepare("UPDATE usuarios
                                                SET id_status = 2
                                                WHERE id_usuario = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['apagado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /usuarioscadastrados');

        break;

    case 'ativar_usuario':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("UPDATE usuarios 
                                                SET id_status = 1
                                                WHERE id_usuario=" . $id);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['ativado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /usuarioscadastrados');

        break;

    case 'atualizar_usuario':
        $id = $_POST['id'];
        $militar = $_POST['militar'];
        $login = $_POST['login'];
        $senha = md5($_POST['senha']);
        $perfil = $_POST['perfil'];
        $apelido = ucwords(strtolower($_POST['apelido']));


        try {
            $stmt = $pdo->prepare("UPDATE usuarios
                                                SET login = ?, senha = ?, id_perfil = ?, nome = ?, id_militar = ?
                                               WHERE id_usuario = ?");
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->bindParam(2, $senha, PDO::PARAM_STR);
            $stmt->bindParam(3, $perfil, PDO::PARAM_INT);
            $stmt->bindParam(4, $apelido, PDO::PARAM_STR);
            $stmt->bindParam(5, $militar, PDO::PARAM_INT);
            $stmt->bindParam(6, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['atualizado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /usuarioscadastrados');

        break;

    case 'combustivel':
        $descricao = ucwords(strtolower($_POST['descricao']));


        try {
            $stmt = $pdo->prepare("INSERT INTO combustiveis
                                                VALUES(NULL,?,$usuario)");
            $stmt->bindParam(1, $descricao, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /combustivel');

        break;

    case 'apagar_combustivel':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM combustiveis
                                                WHERE id_combustivel =" . $id);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE combustiveis
                                                SET id_status = 2
                                                WHERE id_combustivel = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /combustivel');

        break;

    case 'atualizar_combustivel':
        $id = $_POST['id'];
        $descricao = ucwords(strtolower($_POST['descricao']));

        try {
            $stmt = $pdo->prepare("UPDATE combustiveis
                                                SET descricao = ? WHERE id_combustivel = ?");
            $stmt->bindParam(1, $descricao, PDO::PARAM_STR);
            $stmt->bindParam(2, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /combustivel');

        break;

    case 'tipo':
        $descricao = ucwords(strtolower($_POST['descricao']));


        try {
            $stmt = $pdo->prepare("INSERT INTO tipos_combustiveis
                                                VALUES(NULL,?,$usuario)");
            $stmt->bindParam(1, $descricao, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /tiposcombustiveiscadastrados');

        break;

    case 'apagar_tipo':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM tipos_combustiveis
                                                WHERE id_tipo_combustivel= ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE tipos_combustiveis
                                                SET id_status = 2
                                                WHERE id_tipo_combustivel = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /tiposcombustiveiscadastrados');
        break;

    case 'atualizar_tipo':
        $id = $_POST['id'];
        $descricao = ucwords(strtolower($_POST['descricao']));

        try {
            $stmt = $pdo->prepare("UPDATE tipos_combustiveis
                                                SET descricao = ? WHERE id_tipo_combustivel = ?");
            $stmt->bindParam(1, $descricao, PDO::PARAM_STR);
            $stmt->bindParam(2, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /tiposcombustiveiscadastrados');
        break;

    case 'rcb_comb':
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];
        $motivo = mb_strtoupper($_POST['motivo']);


        try {
            $stmt = $pdo->prepare("INSERT INTO recibos_combustiveis
                                                VALUES(NULL,?,?,?,?,NOW(),NOW(),$usuario)");
            $stmt->bindParam(1, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(2, $tp, PDO::PARAM_INT);
            $stmt->bindParam(3, $qnt, PDO::PARAM_INT);
            $stmt->bindParam(4, $motivo, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /recebimentocombustivelcadastrado');

        break;

    case 'apagar_rcb_comb':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM recibos_combustiveis
                                                WHERE id_recibo_combustivel = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();


            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE recibos_combustiveis
                                                SET id_status = 2
                                                WHERE id_recibo_combustivel = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }


        header('Location: /recebimentocombustivelcadastrado');

        break;

    case 'atualizar_rcb_comb':
        $id = $_POST['id'];
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];
        $motivo = mb_strtoupper($_POST['motivo']);

        try {
            $stmt = $pdo->prepare("UPDATE recibos_combustiveis
                                                SET id_combustivel = ?, id_tipo_combustivel =?, qnt = ?, motivo = ?, data = NOW(), hora = NOW() WHERE id_recibo_combustivel = ?");
            $stmt->bindParam(1, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(2, $tp, PDO::PARAM_INT);
            $stmt->bindParam(3, $qnt, PDO::PARAM_INT);
            $stmt->bindParam(4, $motivo, PDO::PARAM_STR);
            $stmt->bindParam(5, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /recebimentocombustivelcadastrado');

        break;

    case 'abst':
        $nrvale = $_POST['nrvale'];
        $motorista = $_POST['motorista'];
        $viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];


        try {
            $stmt = $pdo->prepare("INSERT INTO abastecimentos
                                               VALUES(NULL,?,?,?,?,?,?,?,NOW(),NOW(),$usuario)");
            $stmt->bindParam(1, $nrvale, PDO::PARAM_STR);
            $stmt->bindParam(2, $motorista, PDO::PARAM_INT);
            $stmt->bindParam(3, $viatura, PDO::PARAM_INT);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(6, $tp, PDO::PARAM_INT);
            $stmt->bindParam(7, $qnt, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
                header("Location: /percurso");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'apagar_abst':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM abastecimentos
                                                WHERE id_abastecimento = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE abastecimentos
                                                SET id_status = 2
                                                WHERE id_abastecimento = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();

                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'atualizar_abst':
        $id = $_POST['id'];
        $nrvale = $_POST['nrvale'];
        $motorista = $_POST['motorista'];
        $viatura = $_POST['viatura'];
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];
        $odometro = $_POST['odometro'];

        try {
            $stmt = $pdo->prepare("UPDATE abastecimentos
                                                SET nrvale = ?, id_motorista =?, id_viatura = ?, id_combustivel = ?, id_tipo_combustivel = ?, qnt = ?, odometro = ?, hora = NOW(), data = NOW() 
                                                WHERE id_abastecimento =" . $id);
            $stmt->bindParam(1, $nrvale, PDO::PARAM_STR);
            $stmt->bindParam(2, $motorista, PDO::PARAM_INT);
            $stmt->bindParam(3, $viatura, PDO::PARAM_INT);
            $stmt->bindParam(4, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(5, $tp, PDO::PARAM_INT);
            $stmt->bindParam(6, $qnt, PDO::PARAM_INT);
            $stmt->bindParam(7, $odometro, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'abst_especial':
        $nrvale = $_POST['nrvale'];
        $descricao = $_POST['desc'];
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];


        try {
            $stmt = $pdo->prepare("INSERT INTO abastecimentos_especiais
                                               VALUES(NULL,?,?,?,?,?,NOW(),NOW(),$usuario)");
            $stmt->bindParam(1, $nrvale, PDO::PARAM_STR);
            $stmt->bindParam(2, $descricao, PDO::PARAM_STR);
            $stmt->bindParam(3, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(4, $tp, PDO::PARAM_INT);
            $stmt->bindParam(5, $qnt, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
                header("Location: /percurso");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'apagar_abst_especial':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM abastecimentos_especiais
                                                WHERE id_abastecimento_especial = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE abastecimentos_especiais
                                                SET id_status = 2
                                                WHERE id_abastecimento_especial = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();

                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'atualizar_abst_especial':
        $id = $_POST['id'];
        $nrvale = $_POST['nrvale'];
        $descricao = $_POST['desc'];
        $combustivel = $_POST['combustivel'];
        $tp = $_POST['tp'];
        $qnt = $_POST['qnt'];

        try {
            $stmt = $pdo->prepare("UPDATE abastecimentos_especiais
                                                SET nrvale = ?, descricao = ?, id_combustivel = ?, id_tipo_combustivel = ?, qnt = ?, hora = NOW(), data = NOW() 
                                                WHERE id_abastecimento_especial =" . $id);
            $stmt->bindParam(1, $nrvale, PDO::PARAM_STR);
            $stmt->bindParam(2, $descricao, PDO::PARAM_INT);
            $stmt->bindParam(3, $combustivel, PDO::PARAM_INT);
            $stmt->bindParam(4, $tp, PDO::PARAM_INT);
            $stmt->bindParam(5, $qnt, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /abastecimentorealizado');

        break;

    case 'cadastrar_modelo':
        $marca = $_POST['marca_modelo'];
        $modelo = ucwords(strtolower($_POST['modelo']));
        $cap_tanque = $_POST['cap_tanque'];
        $consumo_padrao = $_POST['consumo_padrao'];
        $cap_transp = $_POST['cap_transp'];


        try {
            $stmt = $pdo->prepare("INSERT INTO modelos
                                                VALUES(NULL,?,?,?,?,?,1)");
            $stmt->bindParam(1, $marca, PDO::PARAM_INT);
            $stmt->bindParam(2, $modelo, PDO::PARAM_STR);
            $stmt->bindParam(3, $cap_tanque, PDO::PARAM_INT);
            $stmt->bindParam(4, $consumo_padrao, PDO::PARAM_INT);
            $stmt->bindParam(5, $cap_transp, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /modelocadastrado');

        break;

    case 'apagar_modelo':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM modelos
                                                WHERE id_modelo = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE modelos
                                                SET id_status = 2
                                                WHERE id_modelo = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();

                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagada'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /modelocadastrado');

        break;

    case 'atualizar_modelo':
        $id = $_POST['id'];
        $marca = $_POST['marca_modelo'];
        $modelo = ucwords(strtolower($_POST['modelo']));
        $cap_tanque = $_POST['cap_tanque'];
        $consumo_padrao = $_POST['consumo_padrao'];
        $cap_transp = $_POST['cap_transp'];

        try {
            $stmt = $pdo->prepare("UPDATE modelos
                                                SET id_marca = ?, descricao = ?, cap_tanque = ?, consumo_padrao = ?, cap_transp = ? 
                                                WHERE id_modelo = ?");
            $stmt->bindParam(1, $marca, PDO::PARAM_INT);
            $stmt->bindParam(2, $modelo, PDO::PARAM_STR);
            $stmt->bindParam(3, $cap_tanque, PDO::PARAM_INT);
            $stmt->bindParam(4, $consumo_padrao, PDO::PARAM_INT);
            $stmt->bindParam(5, $cap_transp, PDO::PARAM_INT);
            $stmt->bindParam(6, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /modelocadastrado');

        break;

    case 'marca':
        $marca = ucwords(strtolower($_POST['marca']));

        try {
            $stmt = $pdo->prepare("INSERT INTO marcas
                                                VALUES(NULL,?,1)");
            $stmt->bindParam(1, $marca, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /marcacadastrada');

        break;

    case 'apagar_marca':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM marcas 
                                                WHERE id_marca = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                try {
                    $stmt = $pdo->prepare("UPDATE marcas
                                                SET id_status = 2
                                                WHERE id_marca = ?");
                    $stmt->bindParam(1, $id, PDO::PARAM_INT);
                    $executa = $stmt->execute();

                    if ($executa) {
                        $_SESSION['apagado'] = 1;
                    }
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /marcacadastrada');

        break;

    case 'atualizar_marca':
        $id = $_POST['id'];
        $marca = ucwords(strtolower($_POST['marca']));

        try {
            $stmt = $pdo->prepare("UPDATE marcas
                                                SET descricao = ? 
                                                WHERE id_marca = ?");
            $stmt->bindParam(1, $marca, PDO::PARAM_STR);
            $stmt->bindParam(2, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /marcacadastrada');

        break;

    case 'login':

        $login = $_POST['login'];
        $senha = md5($_POST['senha']);

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total 
                                                FROM usuarios 
                                                WHERE login = ? 
                                                AND senha = ?
                                                AND id_status != 2');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->bindParam(2, $senha, PDO::PARAM_STR);
            $stmt->execute();

            $resultado = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT id_perfil, nome, id_usuario
                                                FROM usuarios 
                                                WHERE login = ?');
            $stmt->bindParam(1, $login, PDO::PARAM_STR);
            $stmt->execute();

            $resultado1 = $stmt->fetch();

            if ($resultado['total'] > 0) {

                session_start();

                $_SESSION['login'] = $resultado1[1];
                $_SESSION['perfil'] = $resultado1[0];
                $_SESSION['usuario'] = $resultado1[2];
                $_SESSION['temposessao'] = time() + 120;

                if ($_SESSION['perfil'] == 1) {
                    header('Location: /percurso');
                }
                if ($_SESSION['perfil'] == 2) {
                    header('Location: /percurso');
                }
                if ($_SESSION['perfil'] == 3) {
                    header('Location: /viaturascadastradas');
                }
                if ($_SESSION['perfil'] == 4) {
                    header('Location: /relatorio');
                }
                if ($_SESSION['perfil'] == 5) {
                    header('Location: /militarescadastrados');
                }
                if ($_SESSION['perfil'] == 6) {
                    header('Location: /percurso_guarda');
                }
            } else {
                session_start();
                $_SESSION['erro'] = 1;
                header('Location: /');
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'cadastrar_manutencao':
        $id_viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $manutencao = $_POST['manutencao'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        try {
            $stmt = $pdo->prepare("INSERT INTO manutencao_viaturas
                                                VALUES(NULL,?,?,?,?,$usuario)");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(3, $manutencao, PDO::PARAM_STR);
            $stmt->bindParam(4, $data, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /manutencaovtrcadastrada');

        break;

    case 'apagar_manutencao':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM manutencao_viaturas
                                                WHERE id_manutencao_viatura = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /manutencaovtrcadastrada');

        break;

    case 'atualizar_manutencao':
        $id = $_POST['id'];
        $id_viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $manutencao = $_POST['manutencao'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        try {
            $stmt = $pdo->prepare("UPDATE manutencao_viaturas
                                                SET id_viatura = ?, odometro = ?, descricao = ?, data = ?, id_usuario = $usuario 
                                                WHERE id_manutencao_viatura = ?");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(3, $manutencao, PDO::PARAM_STR);
            $stmt->bindParam(4, $data, PDO::PARAM_STR);
            $stmt->bindParam(5, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /manutencaovtrcadastrada');

        break;

    case 'cadastrar_alteracao':
        $id_viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $alteracao = $_POST['alteracao'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        try {
            $stmt = $pdo->prepare("INSERT INTO alteracao_viaturas
                                                VALUES(NULL,?,?,?,?,$usuario)");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(3, $alteracao, PDO::PARAM_STR);
            $stmt->bindParam(4, $data, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /alteracaovtrcadastrada');

        break;

    case 'apagar_alteracao':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM alteracao_viaturas
                                                WHERE id_alteracao_viatura = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /alteracaovtrcadastrada');

        break;

    case 'atualizar_alteracao':
        $id = $_POST['id'];
        $id_viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $alteracao = $_POST['alteracao'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        try {
            $stmt = $pdo->prepare("UPDATE alteracao_viaturas
                                                SET id_viatura = ?, odometro = ?, descricao = ?, data = ?, id_usuario = $usuario 
                                                WHERE id_alteracao_viatura = ?");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(3, $alteracao, PDO::PARAM_STR);
            $stmt->bindParam(4, $data, PDO::PARAM_STR);
            $stmt->bindParam(5, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /alteracaovtrcadastrada');

        break;

    case 'cadastrar_disponibilidade':
        $id_viatura = $_POST['viatura'];
        $odometro = $_POST['odometro'];
        $alteracao = $_POST['alteracao'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        try {
            $stmt = $pdo->prepare("INSERT INTO indisponibilidade
                                                    VALUES(NULL,?,?,?,?,1,$usuario,NULL)");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $alteracao, PDO::PARAM_STR);
            $stmt->bindParam(3, $data, PDO::PARAM_STR);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                <strong>Não foi possível acessar a base de dados</strong>
                             </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        try {
            $stmt = $pdo->prepare("UPDATE viaturas
                                                SET id_situacao = 2
                                                WHERE id_viatura = ?");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $executa = $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /disponibilidadevtrcadastrada');

        break;

    case 'apagar_disponibilidade':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM indisponibilidade
                    WHERE id_disponibilidade = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                    <strong>Não foi possível acessar a base de dados</strong>
                                 </div>");
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /disponibilidadevtrcadastrada');

        break;

    case 'atualiza_disponibilidade':
        $id = $_POST['id'];
        $id_viatura = $_POST['id_viatura'];

        try {
            $stmt = $pdo->prepare("UPDATE indisponibilidade
                        SET id_status = 2, data_fim = NOW()
                        WHERE id_disponibilidade = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <strong>Não foi possível acessar a base de dados</strong>
                                     </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        try {
            $stmt = $pdo->prepare("UPDATE viaturas
                                                        SET id_situacao = 1
                                                        WHERE id_viatura = ?");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $executa = $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /disponibilidadevtrcadastrada');

        break;

    case 'cadastrar_acidente':
        $id_viatura = $_POST['viatura_acidente'];
        $id_motorista = $_POST['motorista'];
        $acompanhante = $_POST['acompanhante'];
        $odometro = $_POST['odometro'];
        $acidente = $_POST['acidente'];
        $avarias = $_POST['avarias'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        if (isset($_POST['disponibilidade'])) {
            $disponibilidade = 2;
        } else {
            $disponibilidade = 1;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO acidentes_viaturas
                                                VALUES(NULL,?,?,?,?,?,?,?,?,$usuario)");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $id_motorista, PDO::PARAM_INT);
            $stmt->bindParam(3, $acompanhante, PDO::PARAM_STR);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $acidente, PDO::PARAM_STR);
            $stmt->bindParam(6, $avarias, PDO::PARAM_STR);
            $stmt->bindParam(7, $data, PDO::PARAM_STR);
            $stmt->bindParam(8, $disponibilidade, PDO::PARAM_INT);
            $executa = $stmt->execute();

            /*if ($disponibilidade == 2) {
                try {
                    $stmt = $pdo->prepare("UPDATE viaturas
                                                    SET id_situacao = ?
                                                    WHERE id_viatura = ?");
                    $stmt->bindParam(1, $disponibilidade, PDO::PARAM_INT);
                    $stmt->bindParam(2, $id_viatura, PDO::PARAM_INT);
                    $executa = $stmt->execute();
                } catch (PDOException $e) {
                    echo $e->getMessage();
                } 
                try {
                    $stmt = $pdo->prepare("INSERT INTO indisponibilidade VALUES (NULL, ?, ?, ?, ?, 1, $usuario)");
                    $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
                    $stmt->bindParam(2, $acidente, PDO::PARAM_STR);
                    $stmt->bindParam(3, $data, PDO::PARAM_STR);
                    $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
                    $executa = $stmt->execute();
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }*/

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /acidentevtrcadastrado');

        break;

    case 'apagar_acidente':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM acidentes_viaturas
                                                WHERE id_acidente_viatura = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['apagado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /acidentevtrcadastrado');

        break;

    case 'atualizar_acidente':
        $id = $_POST['id'];
        $id_viatura = $_POST['viatura_acidente'];
        $id_motorista = $_POST['motorista'];
        $acompanhante = $_POST['acompanhante'];
        $odometro = $_POST['odometro'];
        $acidente = $_POST['acidente'];
        $avarias = $_POST['avarias'];
        $data = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data'])));

        if (isset($_POST['disponibilidade'])) {
            $disponibilidade = 2;
        } else {
            $disponibilidade = 1;
        }

        try {
            $stmt = $pdo->prepare("UPDATE acidentes_viaturas
                                                SET id_viatura = ?, id_motorista = ?, acompanhante = ?, odometro = ?, descricao = ?, data = ?, avarias = ?, id_situacao = ?, id_usuario = $usuario 
                                                WHERE id_acidente_viatura = ?");
            $stmt->bindParam(1, $id_viatura, PDO::PARAM_INT);
            $stmt->bindParam(2, $id_motorista, PDO::PARAM_INT);
            $stmt->bindParam(3, $acompanhante, PDO::PARAM_STR);
            $stmt->bindParam(4, $odometro, PDO::PARAM_STR);
            $stmt->bindParam(5, $acidente, PDO::PARAM_STR);
            $stmt->bindParam(6, $data, PDO::PARAM_STR);
            $stmt->bindParam(7, $avarias, PDO::PARAM_STR);
            $stmt->bindParam(8, $disponibilidade, PDO::PARAM_INT);
            $stmt->bindParam(9, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($disponibilidade = 2) {
                $stmt = $pdo->prepare("UPDATE viaturas
                                                SET id_situacao = ?
                                                WHERE id_viatura = ?");
                $stmt->bindParam(1, $disponibilidade, PDO::PARAM_INT);
                $stmt->bindParam(2, $id_viatura, PDO::PARAM_INT);
                $executa = $stmt->execute();
            }

            if (!$executa) {
                print("<div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Não foi possível acessar a base de dados</strong>
                         </div>");
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /acidentevtrcadastrado');

        break;

    case 'cadastrar_militar':

        $nome_completo = htmlspecialchars(ucwords(strtolower($_POST['nome_completo'])));
        $nome = htmlspecialchars(ucwords(strtolower($_POST['nome'])));
        $pg = $_POST['pg'];
        $cpf = $_POST['cpf'];
        if (empty($_POST['data_nascimento'])) {
            $data_nascimento = NULL;
        } else {
            $data_nascimento = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_nascimento'])));
        }
        $estado_natal = htmlspecialchars(strtoupper($_POST['estado_natal']));
        $cidade_natal = htmlspecialchars(ucwords(strtolower($_POST['cidade_natal'])));
        $idt_militar = htmlspecialchars($_POST['idt_militar']);

        try {

            $stmt = $pdo->prepare("INSERT INTO militares (nome,nome_completo, data_nascimento, id_cidade, id_estado, 
                                    idt_militar, cpf, id_posto_grad, id_status, id_usuario) 
                                                    VALUES (?,?,?,?,?,?,?,?,1,$usuario )");

            $stmt->bindParam(1, $nome, PDO::PARAM_STR);
            $stmt->bindParam(2, $nome_completo, PDO::PARAM_STR);
            $stmt->bindParam(3, $data_nascimento, PDO::PARAM_STR);
            $stmt->bindParam(4, $cidade_natal, PDO::PARAM_INT);
            $stmt->bindParam(5, $estado_natal, PDO::PARAM_INT);
            $stmt->bindParam(6, $idt_militar, PDO::PARAM_STR);
            $stmt->bindParam(7, $cpf, PDO::PARAM_STR);
            $stmt->bindParam(8, $pg, PDO::PARAM_STR);

            $executa = $stmt->execute();

            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['cadastrado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /militarescadastrados');

        break;

    case 'atualizar_militar':
        $id_militar = $_POST['id_militar'];
        $nome_completo = htmlspecialchars(ucwords(strtolower($_POST['nome_completo'])));
        $nome = htmlspecialchars(ucwords(strtolower($_POST['nome'])));
        $pg = $_POST['pg'];
        if (empty($_POST['data_nascimento'])) {
            $data_nascimento = NULL;
        } else {
            $data_nascimento = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_nascimento'])));
        }
        $estado_natal = htmlspecialchars(strtoupper($_POST['estado_natal']));
        $cidade_natal = htmlspecialchars(ucwords(strtolower($_POST['cidade_natal'])));
        $idt_militar = $_POST['idt_militar'];
        $cpf = $_POST['cpf'];

        try {

            $stmt = $pdo->prepare("UPDATE  militares 
                                                SET  
                                                nome =  ?,
                                                nome_completo =  ?,
                                                data_nascimento =  ?,
                                                id_cidade =  ?,
                                                id_estado =  ?,
                                                idt_militar =  ?,
                                                cpf =  ?,
                                                id_posto_grad =  ?
                                                WHERE  id_militar = ?;");

            $stmt->bindParam(1, $nome, PDO::PARAM_STR);
            $stmt->bindParam(2, $nome_completo, PDO::PARAM_STR);
            $stmt->bindParam(3, $data_nascimento, PDO::PARAM_STR);
            $stmt->bindParam(4, $cidade_natal, PDO::PARAM_INT);
            $stmt->bindParam(5, $estado_natal, PDO::PARAM_INT);
            $stmt->bindParam(6, $idt_militar, PDO::PARAM_STR);
            $stmt->bindParam(7, $cpf, PDO::PARAM_STR);
            $stmt->bindParam(8, $pg, PDO::PARAM_STR);
            $stmt->bindParam(9, $id_militar, PDO::PARAM_INT);
            $executa = $stmt->execute();


            if (!$executa) {
                $_SESSION['erro'] = 1;
            } else {
                $_SESSION['atualizado'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /militarescadastrados');

        break;


    case 'apagar_militar':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("UPDATE militares
                                                SET id_status = 2
                                                WHERE id_militar = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            $stmt = $pdo->prepare("UPDATE usuarios
                                                SET id_status = 2
                                                WHERE id_militar = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['apagado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /militarescadastrados');

        break;

    case 'ativar_militar':
        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("UPDATE militares
                                                SET id_status = 1
                                                WHERE id_militar = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();
            if ($executa) {
                $_SESSION['ativado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        header('Location: /militarescadastrados');

        break;

    case 'verificar_senha':
        $id = $_POST['id'];
        $senha_antiga = md5($_POST['senha_antiga']);

        try {
            $stmt = $pdo->prepare("SELECT COUNT(id_usuario) AS qnt
                                                FROM usuarios
                                                WHERE id_usuario = ?
                                                AND senha = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->bindParam(2, $senha_antiga, PDO::PARAM_STR);
            $executa = $stmt->execute();
            if ($executa) {
                $usuarios_qnt = $stmt->fetch(PDO::FETCH_OBJ);
                $qnt = $usuarios_qnt->qnt;
                if ($qnt == 1) {
                    echo 1;
                } else {
                    echo 0;
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'alterar_usuario':
        $id = $_POST['id'];
        $login = $_POST['login'];
        $apelido = $_POST['apelido'];
        $senha = md5($_POST['senha']);


        try {
            $stmt = $pdo->prepare("UPDATE usuarios
                                                SET senha = ?, login = ?, nome = ?
                                               WHERE id_usuario = ?");
            $stmt->bindParam(1, $senha, PDO::PARAM_STR);
            $stmt->bindParam(2, $login, PDO::PARAM_STR);
            $stmt->bindParam(3, $apelido, PDO::PARAM_STR);
            $stmt->bindParam(4, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $_SESSION['atualizado'] = 1;
            } else {
                $_SESSION['erro'] = 1;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    default:
        //no action sent
}
unset($_POST, $_GET);
