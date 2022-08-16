<?php
/**
 * WordPress基础配置文件。
 *
 * 这个文件被安装程序用于自动生成wp-config.php配置文件，
 * 您可以不使用网站，您需要手动复制这个文件，
 * 并重命名为“wp-config.php”，然后填入相关信息。
 *
 * 本文件包含以下配置选项：
 *
 * * MySQL设置
 * * 密钥
 * * 数据库表名前缀
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/zh-cn:%E7%BC%96%E8%BE%91_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL 设置 - 具体信息来自您正在使用的主机 ** //
/** WordPress数据库的名称 */
define( 'DB_NAME', 'www.fantasiamacau.com' );

/** MySQL数据库用户名 */
define( 'DB_USER', 'www.fantasiamacau.com' );

/** MySQL数据库密码 */
define( 'DB_PASSWORD', 'Miynk3HtBtSnRySf' );

/** MySQL主机 */
define( 'DB_HOST', 'localhost' );

/** 创建数据表时默认的文字编码 */
define( 'DB_CHARSET', 'utf8' );

/** 数据库整理类型。如不确定请勿更改 */
define( 'DB_COLLATE', '' );

/**#@+
 * 身份认证密钥与盐。
 *
 * 修改为任意独一无二的字串！
 * 或者直接访问{@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org密钥生成服务}
 * 任何修改都会导致所有cookies失效，所有用户将必须重新登录。
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'bhz{I#^Rpu#<7a6e(B;grh*I#IqpAL%qY23_0*q,0Yfl|w.f@f=XH+,7stjW)P.9' );
define( 'SECURE_AUTH_KEY',  'FE_L/,%^moMcaxrzJ%:zh33ex*~);[HA(N>tsem64Dr-P[T?c3X-])1#=zX@aCS3' );
define( 'LOGGED_IN_KEY',    'ZzieL`UN^XVxG;kuC1%R6[fUh^H>_Qe90@4zACI77a);Av{rBdFI@r<Gt[CEOn4y' );
define( 'NONCE_KEY',        'cF|U[]xg?Od,&! {%D>v_#^xQKlqjwu]T/v,;3R[5e~@~8pvr;<$K,6:C*j6z*u&' );
define( 'AUTH_SALT',        '#p=#l=UKra,f4h.d9Yz}3#zC: S47+JhC{;t%3ql*h%(q!DNYWVE^MUQsnsN4)f9' );
define( 'SECURE_AUTH_SALT', 'HNeh69W{%te4fk>%P%hy5_/ILa&xZA/x93:CV.`nR=ZbttK$clP $ETN4M8w.nD6' );
define( 'LOGGED_IN_SALT',   'u?),4jDMuP){XTXTB*9?umNmIb_q[{Bsr:th?lyh$ A[s?3c_f JkTK0rIlvdH{m' );
define( 'NONCE_SALT',       'Uual.2C M;7u*wEamAaVzR}&}S?Z^d[in#,T+8A^{Qb*Nch}bog>M>$Wwg{Zcr$J' );

/**#@-*/

/**
 * WordPress数据表前缀。
 *
 * 如果您有在同一数据库内安装多个WordPress的需求，请为每个WordPress设置
 * 不同的数据表前缀。前缀名只能为数字、字母加下划线。
 */
$table_prefix = 'fan_';

/**
 * 开发者专用：WordPress调试模式。
 *
 * 将这个值改为true，WordPress将显示所有用于开发的提示。
 * 强烈建议插件开发者在开发环境中启用WP_DEBUG。
 *
 * 要获取其他能用于调试的信息，请访问Codex。
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* 好了！请不要再继续编辑。请保存本文件。使用愉快！ */

/** WordPress目录的绝对路径。 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** 设置WordPress变量和包含文件。 */
require_once( ABSPATH . 'wp-settings.php' );
