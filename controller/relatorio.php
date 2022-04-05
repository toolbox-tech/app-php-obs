<?php

include '../include/config.inc.php';



if (isset($_SESSION['login']) == FALSE) {
    header('Location: /percurso');
} else {

    $verificador = 0;

    $viatura = new Viatura();
    $tabela_relacao_vtr = $viatura->ViaturasRodandoRelatorio();

    $menus = new Menu();
    $menu = $menus->SelecionarMenu($_SESSION['perfil']);

    if (isset($_POST['enviar']) && $_POST['enviar'] == "relatorio_completo") {

        $verificador = 1;

        $relatorios = new Relatorio();
        $relacao_relatorio = $relatorios->listarPercursosCompleto();

        $smarty->assign('verificador', $verificador);
        $smarty->assign('titulo', 'Relatórios');
        $smarty->assign('titulo1', 'Relatório Completo de Vtr');
        $smarty->assign('relacao_relatorio', $relacao_relatorio);
        $smarty->assign('tabela_relacao_vtr', $tabela_relacao_vtr);
        $smarty->assign('login', $_SESSION['login']);
        $smarty->display('./headers/header_datatables.tpl');
        $smarty->display($menu);
        $smarty->display('relatorio.tpl');
        $smarty->display('./footer/footer_relatorio.tpl');
    } else {

        if (!isset($_POST['data_inicio'])) {

            $smarty->assign('verificador', $verificador);
            $smarty->assign('titulo', 'Relatórios');
            $smarty->assign('login', $_SESSION['login']);
            $smarty->display('./headers/header_datatables.tpl');
            $smarty->display($menu);
            $smarty->display('relatorio.tpl');
            $smarty->display('./footer/footer_relatorio.tpl');
        } else {

            $verificador = 1;
            $data_inicio = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_inicio'])));
            $data_fim = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_fim'])));

            $relatorios = new Relatorio();
            $relacao_relatorio = $relatorios->listarPercursos($data_inicio, $data_fim);

            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];

            $smarty->assign('verificador', $verificador);
            $smarty->assign('titulo', 'Relatórios');
            $smarty->assign('titulo1', 'Relatórios de ' . $data_inicio . ' a ' . $data_fim);
            $smarty->assign('relacao_relatorio', $relacao_relatorio);
            $smarty->assign('tabela_relacao_vtr', $tabela_relacao_vtr);
            $smarty->assign('login', $_SESSION['login']);
            $smarty->display('./headers/header_datatables.tpl');
            $smarty->display($menu);
            $smarty->display('relatorio.tpl');
            $smarty->display('./footer/footer_relatorio.tpl');
        }
    }
}
?>