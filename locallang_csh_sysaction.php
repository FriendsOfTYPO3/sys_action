<?php
/**
* Default  TCA_DESCR for "sys_action"
*/

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'An \'Action\' is a configuration of a specific task which may be performed by assigned usergroups via the Task Center.',
		'.details' => 'Currently an Action may be configured to create additional backend usergroups without being and admin-user and by selecting from a limited set of options. Another option is to let an action initiate a SQL SELECT-query in the database and return the result as a CSV file.
Actions can be assigned to a Backend usergroup and they are activated by a single click in the Task Center.',
		'title.description' => 'Enter the title of the action. This is shown in the list of available actions in the Task Center.',
		'description.description' => 'Describe what the action does or allows to do.',
		'hidden.description' => 'Check this option if you wish to disable the availability of the action or non-admin users.',
		'hidden.details' => 'This option is a great way to disable an action during changes made to it because it still allows you as an \'Admin\'-user to activate it for test purposes.',
		'type.description' => 'Select the action type.',
		'type.details' => '<strong>"Create Backend User"</strong> allows to create backend users with a limited set of options. This action type is meant for semi-administrators among your users which is in charge of daily user administration - still without being full fledged \'Admin\'-users with \'a License to Kill everything\'.
When you select this option, you get to enter a \'template\' user, enter a prefix which the new users will automatically have and whether or not a user home-dir is created in the proces. Finally you can select a limited number of usergroups which the can be selected among.

<strong>"SQL-query"</strong> allows to make a fixed SQL SELECT-query in the database returned as CSV lists. When you have created an action of this type, you need to go to the Tools>DBint module and enter the Advanced Search feature. Here you can design your SQL-query. When you have designed it to select what you want correctly, you can select this action (by it\'s name) and save the query to the action there. From that point it will be effective from the Task Center. (Notice that the form of output selected in the Advanced Search function is also stored, so make sure to select CSV output there!)
',
		'assign_to_groups.description' => 'Select the backend users groups allowed to activate the action in the Task Center.',
		't1_userprefix.description' => 'Enter a prefix which is forcibly prepended to new usernames (eg. "news_")',
		't1_allowed_groups.description' => 'Enter the Backend user groups which the user performing the action is able to choose among (if any).',
		't1_create_user_dir.description' => 'If checked, a private home-directory is also created during user creation. ',
		't1_create_user_dir.details' => '<strong>Notice:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] must be configured correctly along with $TYPO3_CONF_VARS["BE"]["lockRootPath"] and writable!',
		't1_copy_of_user.description' => 'Insert a current Backend user which will be used as a template for the new users created. ',
		't1_copy_of_user.details' => 'All values are copied to the new user, except username, password, name and email is of course overridden.
If the template user is a member of a group which is not defined among the "Groups which may be assigned through the action" that group is still set for the user and cannot be removed by the user carrying out the action.',
		't4_recordsToEdit.description' => '[FILL IN] sys_action->t4_recordsToEdit',
		't3_listPid.description' => '[FILL IN] sys_action->t3_listPid',
		't3_tables.description' => '[FILL IN] sys_action->t3_tables',
	),
	'dk' => Array (
	),
	'de' => Array (
	),
	'no' => Array (
	),
	'it' => Array (
	),
	'fr' => Array (
	),
	'es' => Array (
	),
	'nl' => Array (
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => '\'Toiminto\' (\'Action\') on erityisen tapahtuman konfiguraatio joka voidaan m��ritell� erityiselle k�ytt�j�ryhm�lle Tapahtuma Keskuksessa (Task Center).',
		'.details' => 'Nykyisell��n Toimnto voidaankonfiguroida luomalla ylim��r�inen taustak�ytt�j�ryhm� olematta admin-k�ytt�ji� sek� valitsemalla rajoitettu joukko vaihtoehtoisia valintoja. Toinen mahdollisuus on antaa Toiminnon k�ynnist�� SQLSELECT-kysely tietokantaan ja saatu tulos voidaan palauttaa CSV tiedostona.
Toiminnot voidaan m��ritell� Tausta k�ytt�j�ryhm�lle ja ne voidaan aktivoida yhdell� n�ps�ytyksell� Tapahtuma Keskuksessa (Task Center).',
		'title.description' => 'Anna Toiminnon otsikko. T�m� n�kyy mahdollisten toimintojen listalla Tapahtuma Keskuksessa (Task Center).',
		'description.description' => 'Kuvaa mit� toiminto tekee tai mit� se mahdollistaa teht�v�ksi.',
		'hidden.description' => 'Merkkaaa t�m� vaihtoehto jos haluat poistaa valinnan mahdollisuuden tai poistaa sen ei-admin k�ytt�jilt�.',
		'hidden.details' => 'T�m� vaihtoehto on tehokas t�m� poistaa toiminto k�yt�st� muutosten ajaksi koska se silti j�� Admin k�ytt�jille mahdolliseksi.',
		'type.description' => 'Valitse toiminnon tyyppi.',
		'type.details' => '<strong>"Luo Tausta k�ytt�j�"</strong> mahdollistaa taustak�ytt�j�n luomisen jolla on rajoitettu m��r� toimintaoikeuksia. T�m� toiminto on tarkoitettu puoli-administraattoreille jotka vastaavat p�ivitt�isest� k�ytt�j�hallinnasta - kuitenkin ilman lisenssi� "Ammu kaikki eteentuleva". Kun valitset t�m�n vaihtoehdon, on Sinun annettav a "malli" k�ytt�j�, annettava etuliitejoka lis�t��n uudelle k�ytt�j�lle automaattisesti ja luodaanko t�lle k�ytt�j�lle kotihakemisto. Lopuksi valitset rajoitetun joukon k�ytt�j�ryhmi� joista voidaan valita.

<strong>"SQL-kysely"</strong> mahdollistaa kiinte�n SQL SELECT-kyselyn tekemisen tietokannasta joka palauttaa CSV listan. Luodessasi t�m�n tyyppisen toiminnon, tarvitsee Sinun siirty� Ty�kalut>DBint (Tools>DBint) aliohjelmaan ja siell� Laajennettu Haku (Advanced Search). Siell� suunnittele SQL-kyselyn.Kun olet suunnitellut sen hakemaan oikein haluamasi tiedot,voit valita t�m�n toiminnon (sen nimell�) ja tallentaa kyselyn toiminoksi. N�in sen valittavissa toiminoksi  Tapahtuma Keskuksessa (Task Center). (Huomaa ett� tulosteen haluttu muoto valitaan, tallentaen se, Laajennettu Haku (Advanced Search) osassa, joten varmista ett� se on CSV tuloste.',
		'assign_to_groups.description' => 'Anna Tausta k�ytt�j�ryhm�t joilla on oikeus k�ynnist�� toiminto Tapahtuma Keskuksessa (Task Center).',
		't1_userprefix.description' => 'Anna etuliite joka lis�t��n (aina) uusiin k�ytt�j�nimiin. (Esim. "news_").',
		't1_allowed_groups.description' => 'Anna Taustak�ytt�j�ryhm�t jotka toimintoa suorittava k�ytt�j� on oikeutettu valitsemaan.',
		't1_create_user_dir.description' => 'Jos merkattu, yksityinen koti-hakemisto luodaan samalla kuin k�ytt�j�n muut tiedot.',
		't1_create_user_dir.details' => '<strong>Huomaa:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] tulee olla konfiguroitu oikei, kuten my�s $TYPO3_CONF_VARS["BE"]["lockRootPath"] ja niiden tulee olla kirjoitettavissa!',
		't1_copy_of_user.description' => 'Anna nykyinen Tausta k�ytt�j� jota k�ytet��n mallina uusi k�ytt�ji� luotaessa.',
		't1_copy_of_user.details' => 'Kaikki tiedot kopioidaan uudelle k�ytt�j�lle, paitsi k�ytt�j�tunnus, salasana, nimi ja s�hk�postiosoite.
Jos mallin k�ytt�j� onjonkin ryhm�n j�sen jota ei ole m��ritelty "Ryhm��n joka voidaan asettaa toiminnolla", asetetaan t�m� ryhm�joka tapauksessa k�ytt�j�lle eik� sit� voi poistaa k�ytt�j� joka suorittaa toiminnon.',
	),
	'tr' => Array (
	),
	'se' => Array (
	),
	'pt' => Array (
	),
	'ru' => Array (
	),
	'ro' => Array (
	),
	'ch' => Array (
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
	),
	'hu' => Array (
		'.description' => 'Egy \'M�velet\' egy adott feladat be�ll�t�sa, amit egy hozz�rendelt felhaszn�l�csoport hajthat v�gre a Feladatk�zponton kereszt�l.',
		'.details' => 'Jelenleg egy M�velet be�ll�that�, hogy l�trehozzon backend felhaszn�l�csoportokat an�lk�l, hogy admin-felhaszn�l� lenne �s csak korl�tozott jogai lenn�nek. Egy m�sik lehet�s�g engedni, hogy egy m�velet elind�tson egy SQL SELECT-lek�rdez�st �s a visszakapott eredm�nyt CSV file-ba mentse.
A M�veletek backend felhaszn�l�csoportokhoz rendelhet�k �s egy egyszer� kattint�ssal aktiv�lhat�k.',
		'title.description' => 'Add meg a m�velet megnevez�s�t. Ez fog megjelenni a Feladatk�zpontban az el�rhet� m�veletek k�z�tt.',
		'description.description' => '�rd le, mit csin�l �s mit enged�lyez a m�velet.',
		'hidden.description' => 'V�laszd ki, ha nem-admin felhaszn�l�k sz�m�ra nem akarod el�rhet�v� tenni a m�veletet.',
		'hidden.details' => 'Ez egy nagyszer� m�dja, hogy kikapcsold a m�veletet vb�ltoztat�sok alatt, mivel ez lehet�v� teszi neked, mint admin-felhaszn�l�nak a m�velet aktiv�l�s�t teszt c�lokra.',
		'type.description' => 'V�laszd ki a m�velet t�pus�t.',
		'type.details' => 'A <strong>"Backend felhaszn�l� l�trehoz�sa"</strong> lehet�v� teszi �j backend felhaszn�l�k k�sz�t�s�t korl�tozott jogokkal. Ez k�z�p-adminisztr�torok sz�m�ra fontos, akik a napi felhaszn�l� karbantart�st v�gzik - an�lk�l, hogy teljes joggal rendelkezzenek b�rmi ki�rt�s�hoz. Ha kiv�lasztod, meg kell adnod egy sablon felhaszn�l�t, egy prefixet, amivel az �j felhaszn�l�k neve kezd�dni fog, �s megadhatsz egy home-k�nyvt�rat is. V�g�l n�h�ny felhaszn�l�csoportot is megadhatsz, ahova tartozzon.

Az <strong>"SQL-lek�rdez�s"</strong> SELECT-lek�rdez�sek eredm�ny�t menti egy CSV �llom�nyba. Egy ilyen t�pus� m�velet l�trehoz�s�n�l el kell menni az Eszk�z�k>DBint modulba �s bejel�lni a B�v�tett keres�s opci�t. Itt tudsz SQL-lek�rdez�st tervezni. Amikor megtervezted, hogy mit akarsz lek�rdezni, kiv�laszthatod ezt a m�veletet (a nev�vel) �s elmentheted a lek�rdez�st a m�velethez. Ett�l kezdve el�rhet� lesz a Feladatk�zpontban. (Figyelj�nk, hogy a form kimenet �rt�ke a B�v�tett keres�sben szint�n t�rol�dik, v�lasszuk a CSV kimenetet!)',
		'assign_to_groups.description' => 'Add meg azon backend felhaszn�l�csoportokat, melyek aktiv�lhatj�k a m�veletet a Feladatk�zpontban.',
		't1_userprefix.description' => 'Adj meg egy k�telez� prefixet az �j felhaszn�l�neveknek (pl. "news_")',
		't1_allowed_groups.description' => 'Add meg azon backend felhaszn�l�csoportokat, melyek k�z�l a m�veletet elind�t� felhaszn�l� v�lasztani tud (ha vannak).',
		't1_create_user_dir.description' => 'Ha kijel�lt, akkor egy saj�t home-k�nyvt�r is l�trej�n a felhaszn�l� l�trehoz�s k�zben.',
		't1_create_user_dir.details' => '<strong>Figyelem:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] -t megfelel�en be kell �ll�tani, �s �rhat�v� tenni a $TYPO3_CONF_VARS["BE"]["lockRootPath"] -val egy�tt.',
		't1_copy_of_user.description' => 'Az aktu�lis backend felhaszn�l� be�ll�t�sa sablonk�nt az �j felhasz�l� l�trehoz�s�hoz.',
		't1_copy_of_user.details' => 'Az �sszes �rt�k m�solva lett az �j fe�haszn�l�hoz, kiv�ve a felhaszn�l�nevet, jelsz�t, nevet �s email c�met term�szetesen. 
Ha a sablon felhaszn�l� tagja volt egy csoportnak, ami nincs be�ll�tva a m�velethez, akkor a csoport megmarad a felhaszn�l�nak �s nem t�vol�thatja ezt el a m�veletet futtat� felhaszn�l�.',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
		'.description' => 'Uma \'A��o\' � a configura��o de uma tarefa espec�fica, a qual pode ser executada por determinados grupos de usu�rios, atrav�s do Centro de Tarefas.',
		'.details' => 'No momento, uma A��o pode ser configurada para criar gupos de usu�rios de administra��o extras sem ser um usu�rio-admin e selecionando um conjunto limitado de op��es.
Outra op��o � deixar que uma a��o inicie uma pesquisa SQL SELECT no banco de dados e devolva os resultados na forma de um arquivo CSV.
A��es podem ser delegadas a um grupo de usu�rio de administra��o e s�o ativadas com um �nico clique no Centro de Tarefas.',
		'title.description' => 'Digite o t�tulo da a��o. Este ser� mostrado na lista de a��es dispon�veis no Centro de Tarefas.',
		'description.description' => 'Descreva o que a a��o faz ou permite que se fa�a.',
		'hidden.description' => 'Marque esta op��o se deseja desabilitar a disponibilidade da a��o a usu�rios n�o-administradores.',
		'hidden.details' => 'Esta op��o � uma boa maneira de desabilitar uma a��o durante modifica��es feitas a ela, porque permite a voc�, como usu�rio-administrador, ativ�-la para a realiza��o de testes.',
		'type.description' => 'Selecione o tipo da a��o.',
		'type.details' => '<strong>"Criar Usu�rio de Administra��o"</strong> permite criar usu�rios de administra��o com um conjunto limitado de op��es. Este tipo de a��o � para usu�rios semi-administradores respons�veis por manuten��o di�ria - mas sem ser um usu�rio \'Admin\' completo com uma \'Licen�a para Matar tudo\'.
Ao selecionar esta op��o, voc� pode inserir um usu�rio \'modelo\', inserir um prefixo que ser� gerado automaticamente para os novos usu�rios e selecionar se ser� criado ou n�o um diret�rio-raiz no processo. Finalmente, voc� pode selecionar um n�mero limitado de grupos de usu�rios que podem ser selecionados junto.

<strong>"Pesquisa-SQL"</strong> permite realizar uma pesquisa fixa do tipo SQL SELECT no banco de dados e retornar como listas CSV. Ao criar uma a��o deste tipo, voc� deve ir para o m�dulo Ferramentas>DBint e entrar na fun��o Pesquisa Avan�ada. Quando voc� tiver configurado para selecionar corretamente o que voc� deseja, voc� pode selecionar esta a��o (por seu nome) e ali salvar a pesquisa para a a��o. Da� em diante estar� ativado no Centro de Tarefas. (Note que a forma de resultado selecionada na Pesquisa Avan�ada tamb�m � guardada, portanto certifique-se de selecionar o resultado em CSV ali!)',
		'assign_to_groups.description' => 'Selecionar os grupos de usu�rios de administra��o autorizados a ativar a a��o no Centro de Tarefas.',
		't1_userprefix.description' => 'Insira um prefixo que ser� inclu�do nos novos nomes de usu�rio (ex: "noticias_")',
		't1_allowed_groups.description' => 'Insira os grupos de usu�rios de administra��o que podem ser selecionados pelo usu�rio executando a a��o.',
		't1_create_user_dir.description' => 'Se selecionado, um diret�rio-raiz pessoal tamb�m � criado durante a cria��o do usu�rio.',
		't1_create_user_dir.details' => '<strong>Aten��o:</strong>$TYPO3_CONF_VARS["BE"]["userHomePath"] deve ser configurado corretamente junto com $TYPO3_CONF_VARS["BE"]["lockRootPath"] e com permiss�o para grava��o!',
		't1_copy_of_user.description' => 'Insira um usu�rio de administra��o que ser� usado como modelo para a cria��o de novos usu�rios.',
		't1_copy_of_user.details' => 'Todos os dados s�o copiados para o novo usu�rio, com exce��o de nome de usu�rio, senha, nome e senha.
Se o usu�rio modelo for membro de um grupo que n�o esteja definido entre "Grupos que podem ser definidos atrav�s da a��o", este grupo ainda � ligado ao usu�rio e n�o pode ser removido pelo usu�rio executando a a��o.',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
	),
	'ua' => Array (
	),
	'lv' => Array (
	),
	'jp' => Array (
	),
	'vn' => Array (
	),
	'ca' => Array (
	),
	'ba' => Array (
	),
	'kr' => Array (
	),
);
?>