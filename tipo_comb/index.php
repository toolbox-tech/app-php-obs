﻿<HTML>
   <HEAD>
      <TITLE>Controle de Entrada e Saída de Viaturas</TITLE>
      <meta charset="UTF-8"/>
      <script src="../js/jquery.js"></script>
      <link   href="../css/bootstrap.css" rel="stylesheet">
      <script src="../js/bootstrap.js"></script>
      <script src="../js/script.js"></script>
	  
   </HEAD>
   <BODY>
<?php
    include "verificarLogin.php";
    include"../menu.php";
    include '../sessao.php';
?>


       <fieldset>
           <legend>Cadastro de Combustivel</legend>
                <table border=2px text-align='center' style='width: 40%'>
                    <form action="../executar.php" method="post">
                        <tr>
                            <td>Descrição</td>
                            <td><label for="descricao"><input autofocus class="form-control" type="text" style='width: 150px' id="descricao" name="descricao" placeholder="Descrição" required="required"/></label></td>
                        </tr>
                            <td></td>
                           <td><label><button type="submit" class="btn btn-primary" id="enviar" value="tipocomb" name="enviar">Cadastrar</button></label></td>
                        </tr>
                    </form>
                </table>
       </fieldset>
   </BODY>
</HTML>

<?php
include 'tabela_tipo_comb_cadastrados.php';
?>