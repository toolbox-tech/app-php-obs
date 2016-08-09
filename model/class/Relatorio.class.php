<?php

class Relatorio {

    public function listarPercursos($inicio, $fim) {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT id_percurso, marcas.descricao AS marca, 
                                                modelos.descricao AS modelo, placa, motoristas.apelido AS apelido, 
                                                destinos.nome_destino AS destino, odo_saida, IFNULL(acompanhante,'Sem Acompanhantes') AS acompanhante, 
                                                DATE_FORMAT(data_saida,'%d/%m/%Y') AS data_saida, hora_saida, odo_retorno, DATE_FORMAT(data_retorno,'%d/%m/%Y') AS data_retorno, hora_retorno
                                                FROM percursos, viaturas, motoristas, marcas, modelos, destinos
                                                WHERE data_saida BETWEEN ? AND ?
                                                OR data_retorno BETWEEN ? AND ?
                                                OR data_retorno IS NULL
                                                AND percursos.id_motorista = motoristas.id_motorista
                                                AND percursos.id_viatura = viaturas.id_viatura
                                                AND viaturas.id_marca = marcas.id_marca
                                                AND viaturas.id_modelo = modelos.id_modelo 
                                                AND percursos.id_destino = destinos.id_destino
                                                GROUP BY id_percurso");
            $stmt->bindParam(1, $inicio, PDO::PARAM_STR);
            $stmt->bindParam(2, $fim, PDO::PARAM_STR);
            $stmt->bindParam(3, $inicio, PDO::PARAM_STR);
            $stmt->bindParam(4, $fim, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
     public function listarPercursosCompleto() {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT id_percurso, marcas.descricao AS marca, modelos.descricao AS modelo, placa, motoristas.apelido AS apelido, destinos.nome_destino AS destino, odo_saida, IFNULL(acompanhante,'Sem Acompanhantes') AS acompanhante, DATE_FORMAT(data_saida,'%d/%m/%Y') AS data_saida, hora_saida, odo_retorno, DATE_FORMAT(data_retorno,'%d/%m/%Y') AS data_retorno, hora_retorno
                                                FROM percursos, viaturas, motoristas, marcas, modelos, destinos
                                                WHERE percursos.id_motorista = motoristas.id_motorista
                                                AND percursos.id_viatura = viaturas.id_viatura
                                                AND viaturas.id_marca = marcas.id_marca
                                                AND viaturas.id_modelo = modelos.id_modelo 
                                                AND percursos.id_destino = destinos.id_destino
                                                ORDER BY data_saida, hora_saida");
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function listarVtrUtilizacao($inicio, $fim) {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT count(id_percurso) AS qnt, IFNULL((MAX(p.odo_retorno) - MIN(p.odo_saida)),0) AS KM, m.descricao AS marca, mo.descricao AS modelo, placa
                                                FROM percursos p
                                                RIGHT JOIN viaturas v ON p.id_viatura = v.id_viatura AND p.data_saida BETWEEN ? AND ?
                                                INNER JOIN marcas m ON m.id_marca = v.id_marca
                                                INNER JOIN modelos mo ON mo.id_modelo = v.id_modelo
                                                GROUP BY v.id_viatura
                                                ORDER BY v.id_viatura");
            $stmt->bindParam(1, $inicio, PDO::PARAM_STR);
            $stmt->bindParam(2, $fim, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
    public function listarVtrUtilizacaoCompleto() {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT count(id_percurso) AS qnt, IFNULL((MAX(p.odo_retorno) - MIN(p.odo_saida)),0) AS KM, m.descricao AS marca, mo.descricao AS modelo, placa
                                                FROM percursos p
                                                RIGHT JOIN viaturas v ON p.id_viatura = v.id_viatura
                                                INNER JOIN marcas m ON m.id_marca = v.id_marca
                                                INNER JOIN modelos mo ON mo.id_modelo = v.id_modelo
                                                GROUP BY v.id_viatura
                                                ORDER BY v.id_viatura");
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
     public function listarMotoristaUtilizacao($inicio, $fim) {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT count(id_percurso) AS qnt, IFNULL((SUM(p.odo_retorno) - SUM(p.odo_saida)),0) AS KM, apelido
                                                FROM percursos p
                                                RIGHT JOIN motoristas m ON p.id_motorista = m.id_motorista  
                                                AND p.data_saida BETWEEN ? AND ?                                             
                                                AND p.odo_retorno IS NOT NULL
                                                GROUP BY m.id_motorista
                                                ORDER BY m.id_motorista");
            $stmt->bindParam(1, $inicio, PDO::PARAM_STR);
            $stmt->bindParam(2, $fim, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
     public function listarMotoristaCompleto() {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT count(id_percurso) AS qnt, IFNULL((SUM(p.odo_retorno) - SUM(p.odo_saida)),0) AS KM, apelido
                                                FROM percursos p
                                                RIGHT JOIN motoristas m ON p.id_motorista = m.id_motorista   
                                                WHERE p.odo_retorno > 0
                                                GROUP BY m.id_motorista
                                                ORDER BY m.id_motorista");
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function listarAbastecimento($inicio, $fim) {
        include '../model/conexao.php';
        try {
            $stmt = $pdo->prepare("SELECT SUM(qnt) AS qnt, combustiveis.descricao AS combustivel, tipos_combustiveis.descricao AS tipo,  DATE_FORMAT(data,'%d/%m/%Y') AS data 
                                                FROM abastecimentos, combustiveis, tipos_combustiveis
                                                WHERE data BETWEEN ? AND ?
                                                AND abastecimentos.id_combustivel = combustiveis.id_combustivel
                                                AND abastecimentos.id_tipo_combustivel = tipos_combustiveis.id_tipo_combustivel
                                                GROUP BY abastecimentos.id_combustivel, abastecimentos.id_tipo_combustivel, DAY(data)");
            $stmt->bindParam(1, $inicio, PDO::PARAM_STR);
            $stmt->bindParam(2, $fim, PDO::PARAM_STR);
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
     public function listarAbastecimentoCompleto() {
        include '../model/conexao.php';
        try {
           $stmt = $pdo->prepare("SELECT SUM(qnt) AS qnt, combustiveis.descricao AS combustivel, tipos_combustiveis.descricao AS tipo
                                                FROM abastecimentos, combustiveis, tipos_combustiveis
                                                WHERE abastecimentos.id_combustivel = combustiveis.id_combustivel
                                                AND abastecimentos.id_tipo_combustivel = tipos_combustiveis.id_tipo_combustivel
                                                GROUP BY abastecimentos.id_combustivel, abastecimentos.id_tipo_combustivel");
            $executa = $stmt->execute();

            if ($executa) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                print("<script language=JavaScript>
                           alert('Não foi possível criar tabela.');
                           </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
}
