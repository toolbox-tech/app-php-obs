<?php
include '../include/config.inc.php';

session_start();

if (!isset($_SESSION['login']) || ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 3)) {
    header('Location: '.  constant("HOST").'/percurso');
} else {
    
    $marcas = new Marca();
    $relacao_marcas = $marcas->listarMarcas();

    $menus = new Menu();
    $menu = $menus->SelecionarMenu($_SESSION['perfil']);
    
    if(!isset($_POST['id'])){
        
        $smarty->assign('titulo', 'Cadastro de Marcas');
        $smarty->assign('botao', 'Cadastrar');
        $smarty->assign('evento', 'marca');
        $smarty->assign('relacao_marcas', $relacao_marcas);
        $smarty->assign('login', $_SESSION['login']);
        $smarty->display('./headers/header.tpl');
        $smarty->display($menu);
        $smarty->display('marca.tpl');
        $smarty->display('./footer/footer.tpl');

} else {

        $id = $_POST['id'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM marcas WHERE id_marca = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $dados_marcas = $stmt->fetch(PDO::FETCH_OBJ);
                $id_marca = $dados_marcas->id_marca;
                $descricao = $dados_marcas->descricao;

            } else {
            print("<script language=JavaScript>
                   alert('Não foi possível criar tabela.');
                   </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        
        $smarty->assign('titulo', 'Atualização de Marcas');
        $smarty->assign('botao', 'Atualizar');
        $smarty->assign('evento', 'atualizar_marca');
        $smarty->assign('id_marca', $id_marca);
        $smarty->assign('descricao', $descricao);
        $smarty->assign('relacao_marcas', $relacao_marcas);
        $smarty->assign('login', $_SESSION['login']);
        $smarty->display('./headers/header.tpl');
        $smarty->display($menu);
        $smarty->display('marca.tpl');
        $smarty->display('./footer/footer.tpl');
        }
        
        }
        