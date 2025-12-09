payload:

method
pesquisar
opcao
1
valor
9V8VBYHVENA809297
placa
renavam
captchaResponse
N9UXLD5


resposta
<!-- DEFININDO O MODO DE APRESENTACAO -->





	

	<!--  TILES: CONTEUDO  -->
	















<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
<title>eCRVsp - DETRAN - SÃ£o Paulo</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta HTTP-EQUIV="Cache-Control" CONTENT="no-store">

<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- Bootstrap -->
<link href="/GVR/css/bootstrap.css?v=4" rel="stylesheet" type="text/css"
	 />

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
  <script src="/GVR/js/html5shiv.min.js"></script>
  <script src="/GVR/js/respond.min.js"></script>
<![endif]-->

<!-- JAVASCRIPTS COMUNS -->
<script language="javascript" src="/GVR/js/textzoom.js"
	type="text/javascript"></script>
<script type="text/javascript" src='/GVR/js/app/global.js?v=2'></script>
<script type="text/javascript" src='/GVR/js/app/comumApp.js?v=1'></script>
<script type="text/javascript" src='/GVR/js/comum.js?v=4'></script>
<script type="text/javascript" src="/GVR/js/commons/utils.js"></script>
<script type="text/javascript" src="/GVR/js/commons/detect.js"></script>
<script type="text/javascript" src="/GVR/js/commons/date.js"></script>
<script type="text/javascript" src="/GVR/js/commons/cpf.js"></script>
<script type="text/javascript" src="/GVR/js/commons/cnpj.js"></script>
<script type="text/javascript" src="/GVR/js/commons/email.js"></script>
<script type="text/javascript" src="/GVR/js/commons/validate.js?v=4"></script>
<script type="text/javascript">// Para exibição de erros
var errors = new Array();

var saida = "";



	
function showErrors() {
	if (top.showWait) top.showWait(false);
	if (errors.length > 0) {
		for (var indErro=0; indErro < errors.length; indErro++){
			saida = saida + errors[indErro] + "\n";			
		}
		
		// Tratamento aspas simples
		saida = saida.replace(/&#39;/g, "'");
		
		// Tratamento aspas duplas
		saida = saida.replace(/&quot;/g, '"');
		
		alert(saida);
		
		return true;
	}
	return false;
}</script>
<script type="text/javascript">// Para exibição de Mensagens
var messages = new Array();



	
function showMessages() {
	if (top.showWait) top.showWait(false);
	if (messages.length > 0) {
		for (var indMessage=0; indMessage < messages.length; indMessage++)
		{
			alert(messages[indMessage]);
		}
	}
}
</script>
<script type="text/javascript">//Para chamar uma funcao de Init de Pagina apos o 'onLoad' caso ela exista
function initPage(){	
	
	
	//Caso as paginas que implementam tenha definido o Init	
	try{	
		initValidacaoDigital();	
		initPageImpl();
	}	
	catch(e){			
	}
	
	//DESLIGA O WAIT
	//try{	
	//	top.showWait(false);
	//}	
	//catch(e){			
	//}	
}
</script>
<script type="text/javascript">//Funcao de Voltar para a pagina de Origem 'MAE'. Caso ela nao exista volta pra home
function backToOrig(){	

	
	
	
		top.gotoHome();
	
}
</script>
<!-- FIM JAVASCRIPTS COMUNS -->

<!-- JAVASCRIPTS GREYBOX -->
<script type="text/javascript">
	var GB_ROOT_DIR = "/GVR/js/greybox/";
</script>
<script type="text/javascript" src="/GVR/js/greybox/AJS.js"></script>
<script type="text/javascript" src="/GVR/js/greybox/AJS_fx.js"></script>
<script type="text/javascript" src="/GVR/js/greybox/gb_scripts.js"></script>
<link href="/GVR/js/greybox/gb_styles.css" rel="stylesheet"
	type="text/css" media="all" />
<!-- FIM JAVASCRIPTS GREYBOX -->

<!-- JAVASCRIPT RELOGIO DE SESSÃƒO -->
<script type="text/javascript" src="/GVR/js/sessionClock.js?v=5"></script>

<!-- JAVASCRIPTS VALIDATOR -->
<script type="text/javascript" src='/GVR/js/struts-validation.js'></script>
<!-- FIM JAVASCRIPTS VALIDATOR -->

<!-- JAVASCRIPT DO MENU -->
<!-- <script type="text/javascript" src='/GVR/js/menu/coolmenupro.js'></script>-->
<script type="text/javascript" src='/gever/global/menu_items.jsp'></script>
<!-- FIM JAVASCRIPT DO MENU -->

<!-- STYLE DEFAULT -->
<link href="/GVR/css/theme.css?v=5" rel="stylesheet" type="text/css"
	title="cor" />
	<!-- CSS de contraste -->
<link rel="stylesheet" href="/GVR/css/contraste.css" type="text/css" media="all" title="cinza" />

<link rel="stylesheet" href="/GVR/css/buttons.css">
<link rel="stylesheet" href="/GVR/css/menu/menu_styles.css">


<!-- FIM STYLE DEFAULT-->

<!-- STYLE EXTRA -->

<!-- FIM STYLE EXTRA -->

<!-- JAVASCRIPTS EXTRAS -->

		<script type="text/javascript"
			src="/GVR/js/app/pesquisa/BIN/pesquisaCadVeiculo.js"></script>
	
<!-- FIM JAVASCRIPTS EXTRAS -->


<script type="text/javascript" src='/GVR/js/jquery-1.9.1.js'></script>
<script type="text/javascript" src="/GVR/js/app/sdk-evo/sdk-desktop.js?v=2"></script>
<script src="/GVR/js/bootstrap.min.js" ></script>

<script src="/GVR/js/jquery.cookie.js" ></script>

<!--JAVASCRIPT GLOBAL DO TILES -->
<script type="text/javascript">
var temErros = false;

//Chama a funcao de mensagem de erros
function showErrorsHere(){
	this.temErros = showErrors();
	
	//CHAMA A Function de Calls se houver
	try{
		callAppFunctions();
	}
	catch(e){
	//faz nada
	}
}
</script>

<!--  JavaScript colocados pela Aplicacao -->


<!-- chamada no UNLOAD de cada pagina -->
<script type="text/javascript">
//Chama a funcao de mensagem de erros
function onUnloadCall(){	
	//CHAMA A Function de Calls se houver
	try{
		onUnloadCallImpl();
	}
	catch(e){
	//faz nada
	}
}
</script>
<!--FIM JAVASCRIPT GLOBAL DO TILES -->

<!-- CLOSE POPUPs -->
<script type="text/javascript">
	function closePopUps(){
		try{
			top.closeChildWindow();
		}catch(e){}
	}
</script>
<!-- FIM CLOSE POPUPs -->

<!-- CHAMADA DA MONTAGEM DO MENU -->
<script type="text/javascript">
/* 	var m1 = new COOLjsMenuPRO("menu1", MENU_ITEMS);*/	
var m1 = MENU_ITEMS;
</script>

<!-- PREVINIR O Back do Browser -->
<script>
function noBack(){window.history.forward();}
noBack();
//window.onload=noBack;
//window.onpageshow=function(evt){if(evt.persisted)noBack()}
//window.onunload=function(){void(0)}
</script>
<!-- FIM PREVINIR O Back do Browser -->
<!-- JS para montar o Menu -->
<script type="text/javascript" src='/GVR/js/build_menu.js'></script>
<script language="javascript" src="/GVR/js/acess_texto.js"
	type="text/javascript"></script>
<script language="javascript" src="/GVR/js/styleswitcher.js"></script>
</head>

<BODY onload="initPage();startCountdown('false');showErrorsHere();noBack;"
	onunload="closePopUps();onUnloadCall();" marginheight="0"
	marginwidth="0" topmargin="0">

	














<!--TOPO BANNER - FIM -->
<div class="header">
            <a "href="#" onclick="top.gotoHome();"" id="logo" title="e-crvsp - tecla de atalho (Alt + 0)" accesskey="0">
            	e-crvsp - Serviços de Veículos
           	</a>
           	<!--<a href="http://www.detran.sp.gov.br/" id="logo_detran" target="_blank" title="Portal Detran SP - tecla de atalho (Alt + 4)" accesskey="4"><img src="imagens/logo_detran.png" class="img-responsive visible-sm-block visible-md-block visible-lg-block" alt="logo detransp" /></a>-->
        	<dl>
             <dt>Acessibilidade:</dt>
                <dd><a href="#" onClick="setActiveStyleSheet('cor')" class="acess_cor">Cor padrão</a></dd>
                <dd><a href="#" onClick="setActiveStyleSheet('cinza')" class="acess_pb">Alto contraste</a></dd>
                <dd><a href="#" class="decrease">Diminuir tamanho do texto</a></dd>
                <dd><a href="#" class="increase">Aumentar tamanho do texto</a></dd>
            </dl>
            <span class="tempo">
            	
            		<strong>Tempo restante: </strong> <span id="sessao"></span>
            	
            </span>
        </div>
        
        <!-- menu superior -->
        <div class="navbar navbar-inverse navbar-static-top navbar navbar-reset" id="menu_principal"> 
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand hidden-sm hidden-md hidden-lg" href="index.html">Menu</a>
                </div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li><a href="#" onclick="top.gotoHome();">Home</a></li>
                        <li><a href="https://www.e-crvsp.sp.gov.br/GVR/manual/pdf/MANU-Completo.pdf" target="_blank">Manual do Usuário</a></li>
                        <li><a href="/gever/GVR/ajuda/atendimento_interno.jsp">Central de Atendimento</a></li>
                   		
                   	</ul>
                   	
                   	
	                   	<div style="float:right">
	                   		<span class="nome">Ol&aacute;, ARIANE NEVES FERREIRA DIAS!</span>
	                   	</div>
	                
                   		
                    
                </div>
            </div>
        </div>
        

<div class="container" id="conteudo">
	<div class="row">
		<div class="col-sm-3">
			<div class="panel panel-default panel-inverse menu">
				<div class="panel-heading">Serviços</div>
				<div class="menu1"></div>
			</div>
		</div>
		<div class="col-sm-9">
			

		<form name="ConsultaBINCadVeiculoForm" method="post" action="/gever/GVR/pesquisa/bin/cadVeiculo.do">

			<input type="hidden" name="method" value="iniciarPesquisa">

			<table width="100%" height="100%" border="0" cellspacing="0"
				cellpadding="0" class="texto">
				<tr>
					<td class="tab_cant_sup_esq">&nbsp;</td>
					<td class="tab_sup_fund">Consultar Cadastro BIN / RENAVAM</td>
					<td class="tab_cant_sup_dir">&nbsp;</td>
				</tr>
				<tr>
					<td class="tab_cant_sup_esq2"></td>
					<td></td>
					<td class="tab_cant_sup_dir2"></td>
				</tr>
				<tr height="100%" id="printableContent">
					<td class="tab_bar_esq"></td>
					<td class="texto" valign="top"><!-- AQUI VAI O CONTEUDO DA CELULA -->
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td width="100%">
							<fieldset><legend align="left"><font
								color="#999999" class="fonte_legend">Identifica&ccedil;&atilde;o
							do Ve&iacute;culo na BIN</font></legend>
							<table width="100%" border="0" cellspacing="8" cellpadding="0">
								<tr>
									<td width="25%"><span class="texto_black2">Placa</span></td>
									<td width="30%"><span class="texto_menor">RUD7A21</span></td>
									<td width="25%"><span class="texto_black2">Munic&iacute;pio</span></td>
									<td width="20%"><span class="texto_menor">9701 - BRASILIA</span></td>
								</tr>
								<tr>
									<td width="25%"><span class="texto_black2">Chassi</span></td>
									<td width="30%"><span class="texto_menor">9V8VBYHVENA809297     </span></td>
									<td width="25%"><span class="texto_black2">UF</span></td>
									<td width="20%"><span class="texto_menor">DF</span></td>
								</tr>
								<tr>
									<td width="25%"><span class="texto_black2">Renavam</span></td>
									<td width="30%"><span class="texto_menor">01297078036</span></td>
									<td width="25%"><span class="texto_black2">N&deg;
									do Motor</span></td>
									<td width="20%"><span class="texto_menor">10Q4EW0019165        </span></td>
								</tr>
								<tr>
									<td colspan="4">
									<table width="100%" cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td width="25%"><span class="texto_black2">Situa&ccedil;&atilde;o
											Ve&iacute;culo</span></td>
											<td width="30%"><span class="texto_menor">CIRCULACAO</span></td>
											<td width="25%"><span class="texto_black2">Proced&ecirc;ncia
											Ve&iacute;culo</span></td>
											<td width="20%"><span class="texto_menor">ESTRANGEIRA</span></td>
										</tr>
									</table>
									</td>
								</tr>
								<tr>
									<td colspan="4">
									<table width="100%" cellpadding="0" cellspacing="0">
										<tr>
											
											
												<td width="25%"></td>
												<td width="30%"></td>											
																						
											<td width="25%"><span class="texto_black2">CPF/CNPJ
											Faturado</span></td>
											<td width="20%"><span class="texto_menor">67.405.936/0001-73</span></td>
																						
										</tr>
									</table>
									</td>
								</tr>
							</table>
							</fieldset>

							<fieldset><legend align="left"> <font
								color="#999999" class="fonte_legend">Caracteristicas
							do Ve&iacute;culo</font> </legend>
							<table width="100%" border="0" cellspacing="8" cellpadding="0">
								<tr>
									<td width="20%"><span class="texto_black2">Tipo</span></td>
									<td width="30%"><span class="texto_menor">23 - CAMINHONETE</span></td>
									<td width="15%"><span class="texto_black2">Combust&iacute;vel</span></td>
									<td colspan="5"><span class="texto_menor">3 - DIESEL</span></td>
								</tr>
								<tr>
									<td width="20%"><span class="texto_black2">Cor</span></td>
									<td width="30%"><span class="texto_menor">4 - BRANCA</span></td>
									<td width="15%"><span class="texto_black2">Marca</span></td>
									<td colspan="4"><span class="texto_menor">200326 - I/PEUGEOT EXPERT CARGO</span></td>
								</tr>
								<tr>
									<td width="20%"><span class="texto_black2">Ano
									Modelo</span></td>
									<td width="30%"><span class="texto_menor">2022</span></td>
									<td width="15%"><span class="texto_black2">Ano
									Fabrica&ccedil;&atilde;o</span></td>
									<td colspan="3"><span class="texto_menor">2022</span></td>
								</tr>
																
								
								
								
								<tr>
									<td width="20%"><span class="texto_black2">Tipo
									Remarca&ccedil;&atilde;o de Chassi</span></td>
									<td width="10%"><span class="texto_menor">Normal</span></td>
								</tr>
							</table>
							</fieldset>

							<fieldset><legend align="left"> <font
								color="#999999" class="fonte_legend">Restri&ccedil;&otilde;es</font></legend>
							<table width="100%" border="0" cellspacing="8" cellpadding="0">
								<tr>
									<td width="10%"><span class="texto_black2">Restri&ccedil;&atilde;o</span></td>
									<td width="90%">
										
										
										
											<span class="texto_menor">
												ALIENACAO FIDUCIARIA
											</span>
										
										
										</td>
								</tr>
								<tr>
									<td width="10%"><span class="texto_black2"></span></td>
									<td width="90%"><span class="texto_menor">                    </span></td>
								</tr>
								<tr>
									<td width="10%"><span class="texto_black2"></span></td>
									<td width="90%"><span class="texto_menor">                    </span></td>
								</tr>
								<tr>
									<td width="10%"><span class="texto_black2"></span></td>
									<td width="90%"><span class="texto_menor">                    </span></td>
								</tr>
							</table>
							</fieldset>

							<fieldset><legend align="left"> <font
								color="#999999" class="fonte_legend">Gravames</font> </legend>
							<table width="100%" border="0" cellspacing="8" cellpadding="0">
								<tr>
									<td width="20%"><span class="texto_black2">Tipo de
									Transa&ccedil;&atilde;o</span></td>
									<td width="25%"><span class="texto_menor"></span></td>
									<td width="20%"><span class="texto_black2">Restr.
									Financeira</span></td>
									<td width="25%"><span class="texto_menor"></span></td>
								</tr>
								<tr>
									<td width="20%"><span class="texto_black2">Agente
									Financeiro</span></td>
									<td colspan="5"><span class="texto_menor">                                        </span></td>
								</tr>
								<tr>
									<td width="20%"><span class="texto_black2">Nome
									Financiado</span></td>
									<td colspan="5"><span class="texto_menor">                                        </span></td>
								</tr>
								<tr>
									<td width="20%"><span class="texto_black2">CNPJ/CPF
									Financ</span></td>
									<td width="25%"><span class="texto_menor"></span></td>									
									
									 
								</tr>
								<tr>
									<td><span class="texto_black2">Data Inclus&atilde;o</span></td>
									<td><span class="texto_menor"></span></td>
										
									
									

								</tr>
							</table>
							</fieldset>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td><!-- BOTOES -->
							<table id="tabBotoes" width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr align="right">
									<td>
									
									

									
										<a href="#" onclick="imprimir();" class="bt_imprimir">IMPRIMIR</a>
										<a href="#" onclick="voltar();" class="bt_voltar">VOLTAR</a>
									</td>
								</tr>
							</table>
							</td>
						</tr>
					</table>
					
					
					09/12/2025 13:42:34
					<!--FIM CONTEUDO DA CELULA --></td>
					<td class="tab_bar_dir"></td>
				</tr>
				<tr>
					<td class="tab_cant_inf_esq"></td>
					<td class="tab_inf_fund"></td>
					<td class="tab_cant_inf_dir"></td>
				</tr>
			</table>
		</form>
	
		</div>
	</div>
</div>


<!--RODAPÉ - INÍCIO -->
		 <div class="footer">
            <div class="container">
            	<p class="text-muted"><a href="http://www.prodesp.sp.gov.br/" target="_blank">Prodesp - Tecnologia da Informação</a> - v3.69 (02/12/2025)</p>
            </div>
        </div>
<!--RODAPÉ - FIM -->

	
	
<script>
	$(function () {
    	$(".menu1").append(BuildMenu(MENU_ITEMS));
    });	
    
	$(document).ready(function(){
  		$('.list-group-item-sub a.dropdown-toggle').on("click", function(e){
    		$(this).next('ul').toggle();
    		e.stopPropagation();
    		e.preventDefault();
  		});
	});
	
</script>

	<!-- Aguarde -->
	<div id="waitShow"
		style="visibility: hidden; filter: alpha(opacity =         95); opacity: 0.95; position: absolute; width: 100%; height: 90%; z-index: 9; left: 0px; top: 0px;">
		<table width="100%" height="100%" border="0">
			<tr height="100%">
				<td align="center" valign="middle"><img id='imgWait'
					src="/GVR/imagens/wait.gif" style="cursor: default" alt=""
					border="0">
				</td>
			</tr>
		</table>
	</div>
	<div id="waitTransp"
		style="visibility: hidden; position: absolute; width: 100%; height: 90%; z-index: 10; left: 0px; top: 0px;">
		<img src="/GVR/imagens/nada.gif" style="cursor: wait" width='100%'
			height='100%' border="0" />
	</div>

	<!-- iFRAME MENSAGENS -->
	<iframe name='iframe_mensagem' style="visibility: hidden"
		frameborder='0' width='0' height='0'></iframe>

	<!-- iFrame CallBack -->
	<iframe name="iframe_cb" id="iframe_cb" style="visibility: hidden"
		frameborder='0' width='0' height='0'></iframe>

	<!-- Seta a variavel global com a Data do Servidor -->
	
	<SCRIPT type="text/javascript">
	if(top.setSysDate) top.setSysDate("09/12/2025");
		//data/hora sistema:
	
		
	</SCRIPT>
	
</BODY>
</html>