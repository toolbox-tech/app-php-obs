<?php

include $_SERVER['DOCUMENT_ROOT'] . '/include/config.inc.php';


if (isset($_SESSION['login']) == FALSE || ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 3 && $_SESSION['perfil'] != 4)) {
    header('Location: /percurso');
} else {

    $combustiveis = new Combustivel();
    $relacao_combustiveis = $combustiveis->listarCombustiveisAbastecimento();

    $tipos_combustiveis = new TipoCombustivel();
    $relacao_tipo_combustiveis = $tipos_combustiveis->listarTiposCombustiveisAbastecimento();

    $menus = new Menu();
    $menu = $menus->SelecionarMenu($_SESSION['perfil']);


    if (!isset($_POST['id'])) {

        $smarty->assign('update', '');
        $smarty->assign('id_abastecimento', '');
        $smarty->assign('nrvale', '');
        $smarty->assign('motorista', '');
        $smarty->assign('viatura', '');
        $smarty->assign('odometro', '');
        $smarty->assign('combustivel', '');
        $smarty->assign('tipo_combustivel', '');
        $smarty->assign('qnt', '');
        $smarty->assign('descricao', '');
        $smarty->assign('titulo', 'Cadastro de Abastecimentos Especiais');
        $smarty->assign('botao', 'Cadastrar');
        $smarty->assign('evento', 'abst_especial');
        $smarty->assign('relacao_combustiveis', $relacao_combustiveis);
        $smarty->assign('relacao_tipos_combustiveis', $relacao_tipo_combustiveis);
        if (!empty($_SESSION['cadastrado'])) {
            $smarty->assign('cadastrado', $_SESSION['cadastrado']);
        } else {
            $smarty->assign('cadastrado', FALSE);
        }
        if (!empty($_SESSION['atualizado'])) {
            $smarty->assign('atualizado', $_SESSION['atualizado']);
        } else {
            $smarty->assign('atualizado', FALSE);
        }
        if (!empty($_SESSION['apagado'])) {
            $smarty->assign('apagado', $_SESSION['apagado']);
        } else {
            $smarty->assign('apagado', FALSE);
        }
        $smarty->assign('login', $_SESSION['login']);
        $smarty->display('./headers/header_datatables.tpl');
        $smarty->display($menu);
        $smarty->display('abastecimento_especial.tpl');
        $smarty->display('./footer/footer_datatables.tpl');
        unset($_SESSION['cadastrado']);
        unset($_SESSION['atualizado']);
        unset($_SESSION['apagado']);
    } else {

        $id = $_POST['id'];
        $update = 1;

        try {
            $stmt = $pdo->prepare("SELECT * FROM abastecimentos_especiais WHERE id_abastecimento_especial = ?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $executa = $stmt->execute();

            if ($executa) {
                $dados_abastecimentos = $stmt->fetch(PDO::FETCH_OBJ);
                $id_abastecimento = $dados_abastecimentos->id_abastecimento_especial;
                $nrvale = $dados_abastecimentos->nrvale;
                $descricao = $dados_abastecimentos->descricao;
                $combustivel = $dados_abastecimentos->id_combustivel;
                $tipo_combustivel = $dados_abastecimentos->id_tipo_combustivel;
                $qnt = $dados_abastecimentos->qnt;
            } else {
                print("<script language=JavaScript>
                   alert('Não foi possível criar tabela.');
                   </script>");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        if (!empty($_SESSION['cadastrado'])) {
            $smarty->assign('cadastrado', $_SESSION['cadastrado']);
        } else {
            $smarty->assign('cadastrado', FALSE);
        }
        if (!empty($_SESSION['atualizado'])) {
            $smarty->assign('atualizado', $_SESSION['atualizado']);
        } else {
            $smarty->assign('atualizado', FALSE);
        }
        if (!empty($_SESSION['apagado'])) {
            $smarty->assign('apagado', $_SESSION['apagado']);
        } else {
            $smarty->assign('apagado', FALSE);
        }
        $smarty->assign('titulo', 'Atualização de Abastecimentos');
        $smarty->assign('botao', 'Atualizar');
        $smarty->assign('evento', 'atualizar_abst_especial');
        $smarty->assign('update', $update);
        $smarty->assign('id_abastecimento', $id_abastecimento);
        $smarty->assign('nrvale', $nrvale);
        $smarty->assign('descricao', $descricao);
        $smarty->assign('combustivel', $combustivel);
        $smarty->assign('tipo_combustivel', $tipo_combustivel);
        $smarty->assign('qnt', $qnt);
        $smarty->assign('relacao_combustiveis', $relacao_combustiveis);
        $smarty->assign('relacao_tipos_combustiveis', $relacao_tipo_combustiveis);
        $smarty->assign('login', $_SESSION['login']);
        $smarty->display('./headers/header_datatables.tpl');
        $smarty->display($menu);
        $smarty->display('abastecimento_especial.tpl');
        $smarty->display('./footer/footer_datatables.tpl');
    }
}
