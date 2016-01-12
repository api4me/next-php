<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Article.php
* @touch date Wed 02 Jul 2014 03:16:08 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Article extends \Next\Core\Model {

/*{{{ loadAll */
	/**
	 * 获取所有文章
	 * @param unknown $param
	 * @return multitype:unknown
	 */
	public function loadAll($param){
		$where = array();
		$where[] = '1=1';

		if ($param['type']) {
			$where[] = sprintf('type=%s', $this->db->quote($param['type']));
		}
		if ($param['name']) {
			$where[] = sprintf('name LIKE %s', $this->db->quote('%' . $param['name'] . '%'));
		}
		$q = 'SELECT * FROM buz_article WHERE %s ORDER BY id DESC;';
		$q = sprintf($q, implode(' AND ', $where));
		$query = $this->db->prepare($q);
		$query->execute();
		$out = array();
		while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
			$out[$row['id']] = $row;
		}
		
		return $out;
	}
/*}}}*/
/*{{{ loadById */
	/**
	 * 根据文章ID获取文章
	 * @param unknown $id
	 */
    public function loadById($id) {
        $q = 'SELECT * FROM buz_article WHERE id=:id;';
        $query = $this->db->prepare($q);
        $query->bindParam(':id', $id);
        $query->execute();
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
/*}}}*/
/*{{{ loadByType */
    /**
     * 获取指定类型下的所有文章
     * @param unknown $param
     * @return multitype:NULL
     */
    public function loadByType($param) {
        $out = array();

        $q = 'SELECT COUNT(*) FROM buz_article WHERE type=:type;';
        $query = $this->db->prepare($q);
        $query->bindParam(':type', $param['type']);
        $query->execute();

        if ($tmp = $query->fetchColumn()) {
            $out['total'] = $tmp;
            if (!isset($param['sort'])) {
                $param['sort'] = 'DESC';
            }
            $q = sprintf('SELECT `id`, `name`, `type`, `type_name`, `content` FROM buz_article WHERE `type`=:type ORDER BY id %s LIMIT :start, 20;', $param['sort']);
            $query = $this->db->prepare($q);
            $query->bindParam(':type', $param['type']);
            $query->bindParam(':start', $param['start'], \PDO::PARAM_INT);
            $query->execute();
            $out['data'] = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $out;
        }

        return false;
    }
/*}}}*/
/*{{{ loadOneByType */
    /**
     * 根据类型获取一篇文章
     * @param unknown $type
     */
    public function loadOneByType($type) {
        $q = 'SELECT * FROM buz_article WHERE type=:type LIMIT 1;';
        $query = $this->db->prepare($q);
        $query->bindParam(':type', $type);
        $query->execute();
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
/*}}}*/
/*{{{ edit */
    /**
     * 编辑文章
     * @param unknown $data
     * @return boolean
     */
	public function edit($data){
		if ($this->execute('buz_article', 'up', $data['edit'], $data['where'])) {
			return true;
		}else{
			return false;
		}
	}
/*}}}*/
/*{{{ add */
	/**
	 * 增加新的文章
	 * @param unknown $data
	 * @return boolean
	 */
	public function add($data){
		if ($this->execute('buz_article', 'add', $data)) {
			return true;
		}else{
			return false;
		}
	}
/*}}}*/
/*{{{ delete */
	/**
	 * 删除文章
	 * @param unknown $id
	 * @return boolean
	 */
	public function delete($id){
		if ($this->execute('buz_article', 'delete', array('id'=>$id))) {
			return true;
		}else{
			return false;
		}
	}
/*}}}*/

}
?>
