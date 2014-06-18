<?php

namespace Telenok\Core\Model\Security;

class Permission extends \Telenok\Core\Interfaces\Eloquent\Object\Model {

	protected $ruleList = ['title' => ['required', 'min:1'], 'code' => ['required', 'unique:permission,code,:id:,id', 'regex:/^[A-Za-z][A-Za-z0-9_.-]*$/']];
	protected $table = 'permission';

	public function setCodeAttribute($value)
	{
		$this->attributes['code'] = str_replace(' ', '', strtolower($value));
	}

	public function aclPermission()
	{
		return $this->hasMany('\Telenok\Core\Model\Security\SubjectPermissionResource', 'acl_permission_permission');
	}

}

?>