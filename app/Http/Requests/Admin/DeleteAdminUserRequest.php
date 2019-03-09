<?php

namespace App\Http\Requests\Admin;

class DeleteAdminUserRequest extends AdminRequest
{
    public $permissionName = 'delete_admin_user';
}
