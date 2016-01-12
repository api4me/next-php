<?php
namespace app\control\shop;

class Game extends \Next\Core\Control {
    private $user;

    public function __construct() {
        parent::__construct();
        $this->user = $this->app->session->get('user');
    }

/*{{{ index */
/** page 首页游戏列表 **/
    public function index() {

    }
/*}}}*/
/*{{{ knowledge  */
/** page game：Water Knowledge */
    public function knowledge() {
    	$out = array();

    	$config = $this->app->config('wechat');
    	$out['appid'] = $config['appid'];
        $out['counter'] = 500 + $this->app->redis->incr('g.kn') * 7;
    	$this->display('site/game_knowledge.html', $out);
    }
/*}}}*/

}
?>
