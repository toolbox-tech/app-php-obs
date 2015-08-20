<HTML>
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
    //Inserido para criar o erro de inserção
    session_start();
            if($_SESSION['mysql'] == 1){
               echo "  <div class='alert alert-danger alert-dismissible' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                            <strong>Erro ao inserir dados</strong>
                         </div>";
            }
            //Inserido para criar o erro de inserção
?>
       
       <fieldset>
           <legend>Controle de Saída de Viatura</legend>
                <table class="table" text-align='center' style='width: 100%'>
                    <tr>
                        <td>Viatura - Placa</td>
                        <td>Motorista</td>
                        <td>Destino</td>
                        <td>Odômetro Saída</td>
                        <td>Ch Vtr</td>
                        <td></td>
                    </tr>
                    <tr>
	            	<form action="../executar.php" method="post">
                            <td><label for="viatura" ><select class="form-control" name="viatura" required="required">
                                                            <?php
                                                            include 'relacao_vtr.php';
                                                            ?>
                                </select></label></td>
                        <td><label for="motorista"><select class="form-control" name="motorista" required="required">
                                                            <?php
                                                            include 'relacao_motorista.php';
                                                            ?>
                                </select></label></td>
                        <td><label for="destino"><input class="form-control" type="text" style='width: 150px' id="destino" name="destino" placeholder="Destino" required="required"/></label><br /></td>
                        <td><label for="odo_saida"><input class="form-control" type="number" style='width: 150px' id="odo_saida" name="odo_saida" placeholder="Odometro Saida" required="required"/></label></td>
                        <td><label for="ch_vtr"><input class="form-control" type="text" style='width: 150px' id="ch_vtr" name="ch_vtr" placeholder="Ch Vtr" required="required"/></label></td>
                        <td><label><button type="submit" class="btn btn-primary" id="enviar" value="Cadastrar" name="enviar">Cadastrar</button></label></td>
                    </tr>
                </table>
            </form>
       </fieldset>
   </BODY>
</HTML>



<?php
include 'tabela_relacao_vtr.php';
include 'tabela_relacao_vtr_fechadas.php';
?>