<?php
/**
 * As configurações básicas do WordPress
 *
 * O script de criação wp-config.php usa esse arquivo durante a instalação.
 * Você não precisa usar o site, você pode copiar este arquivo
 * para "wp-config.php" e preencher os valores.
 *
 * Este arquivo contém as seguintes configurações:
 *
 * * Configurações do banco de dados
 * * Chaves secretas
 * * Prefixo do banco de dados
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Configurações do banco de dados - Você pode pegar estas informações com o serviço de hospedagem ** //
/** O nome do banco de dados do WordPress */
define( 'DB_NAME', 'jabuka' );

/** Usuário do banco de dados MySQL */
define( 'DB_USER', 'root' );

/** Senha do banco de dados MySQL */
define( 'DB_PASSWORD', 'assassin5' );

/** Nome do host do MySQL */
define( 'DB_HOST', 'localhost' );

/** Charset do banco de dados a ser usado na criação das tabelas. */
define( 'DB_CHARSET', 'utf8mb4' );

/** O tipo de Collate do banco de dados. Não altere isso se tiver dúvidas. */
define( 'DB_COLLATE', '' );

/**#@+
 * Chaves únicas de autenticação e salts.
 *
 * Altere cada chave para um frase única!
 * Você pode gerá-las
 * usando o {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org
 * secret-key service}
 * Você pode alterá-las a qualquer momento para invalidar quaisquer
 * cookies existentes. Isto irá forçar todos os
 * usuários a fazerem login novamente.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '#|Gb0kA db!r5{2iHIVVID0E#:osa]Hp/z<Z2F(gz{8;VhPJ3`:eNm;dN!mxq]L2' );
define( 'SECURE_AUTH_KEY',  ';5FuycHQ-uN;@*kc:}X2,p=&,q8nj*bbZ{_F<Z {14  eRcm6jIH}221Mgi7F&69' );
define( 'LOGGED_IN_KEY',    'WOa^E8YY*`auKqUV_/(cIa@epo2HPUoMm5gp[S$nM8m3<@jmH05BDj}<_BE745m%' );
define( 'NONCE_KEY',        'M[C^81Nksil]9%dw!`F95D8<k<HB-I`{mC>hJ@eZ)bLK3>)U&y)J/SX-ihZUNq64' );
define( 'AUTH_SALT',        '?&KD`[/]6K,|%B13,@C xqy>m?1w<Bn8c$T~=g;1<WBB-do&zZNZIW0btTGTHkkJ' );
define( 'SECURE_AUTH_SALT', 'oH}v{%N<ip,dV9E<KP(VQTfh5)q,&+:af@:V+Y)VQIL;I8ZDCE$L,.PwH.6ZkEHB' );
define( 'LOGGED_IN_SALT',   'GMs?vsXU~np$u|Xnl$+*pg|A.eu<Ee]piGa=2?whTG)(boHREyQrQaYCGxzMq-.Y' );
define( 'NONCE_SALT',       'rN0a8|xngy[-x#81_kAtkCaWHQ/n0YhNp#<g,M-PR9kh6f9=$f4Q`<Gq0M#D]ee>' );

/**#@-*/

/**
 * Prefixo da tabela do banco de dados do WordPress.
 *
 * Você pode ter várias instalações em um único banco de dados se você der
 * um prefixo único para cada um. Somente números, letras e sublinhados!
 */
$table_prefix = 'wp_';

/**
 * Para desenvolvedores: Modo de debug do WordPress.
 *
 * Altere isto para true para ativar a exibição de avisos
 * durante o desenvolvimento. É altamente recomendável que os
 * desenvolvedores de plugins e temas usem o WP_DEBUG
 * em seus ambientes de desenvolvimento.
 *
 * Para informações sobre outras constantes que podem ser utilizadas
 * para depuração, visite o Codex.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Adicione valores personalizados entre esta linha até "Isto é tudo". */



/* Isto é tudo, pode parar de editar! :) */

/** Caminho absoluto para o diretório WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Configura as variáveis e arquivos do WordPress. */
require_once ABSPATH . 'wp-settings.php';
