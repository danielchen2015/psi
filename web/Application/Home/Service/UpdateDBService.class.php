<?php

namespace Home\Service;

use Think\Think;

/**
 * 数据库升级Service
 *
 * @author 李静波
 */
class UpdateDBService extends PSIBaseService {
	
	/**
	 *
	 * @var \Think\Model
	 */
	private $db;

	public function updateDatabase() {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = M();
		
		$this->db = $db;
		
		// 检查t_psi_db_version是否存在
		if (! $this->tableExists($db, "t_psi_db_version")) {
			return $this->bad("表t_psi_db_db_version不存在，数据库结构实在是太久远了，无法升级");
		}
		
		// 检查t_psi_db_version中的版本号
		$sql = "select db_version from t_psi_db_version";
		$data = $db->query($sql);
		$dbVersion = $data[0]["db_version"];
		if ($dbVersion == $this->CURRENT_DB_VERSION) {
			return $this->bad("当前数据库是最新版本，不用升级");
		}
		
		$sql = "delete from t_psi_db_version";
		$db->execute($sql);
		$sql = "insert into t_psi_db_version (db_version, update_dt) 
				values ('%s', now())";
		$db->execute($sql, $this->CURRENT_DB_VERSION);
		
		$bl = new BizlogService();
		$bl->insertBizlog("升级数据库表结构，数据库表结构版本 = " . $this->CURRENT_DB_VERSION);
		
		return $this->ok();
	}
}