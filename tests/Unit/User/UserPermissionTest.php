<?php
/**
 * Created by PhpStorm.
 * User: darryldecode
 * Date: 3/3/2018
 * Time: 12:43 AM
 */

namespace Tests\Unit\User;


use App\Components\User\Models\Group;
use App\Components\User\Models\Permission;
use App\Components\User\Models\User;
use App\Components\User\Repositories\MySQLUserRepository;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    /**
     * @var MySQLUserRepository
     */
    protected $userRepo;

    protected $group;

    protected $permission;

    public function setUp()
    {
        parent::setUp();
        $this->userRepo = new MySQLUserRepository();
        $this->group = factory(Group::class)->create();
        $this->permission = factory(Permission::class)->create();
    }

    public function test_it_can_check_if_user_has_special_permission()
    {
        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [
                ['key'=>$this->permission->key, 'value'=>User::PERMISSION_ALLOW]
            ],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => [
                $this->group->id => true
            ]
        ])->getData();

        $this->assertTrue($user->hasPermission($this->permission->key));
        $this->assertTrue($user->hasAnyPermission([$this->permission->key]));
    }

    public function test_it_can_add_user_special_permission()
    {
        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => []
        ])->getData();

        // remove
        $user->addPermission($this->permission,User::PERMISSION_ALLOW);

        $this->assertTrue($user->hasPermission($this->permission->key));
        $this->assertTrue($user->hasAnyPermission([$this->permission->key]));
    }

    public function test_it_can_remove_user_special_permission()
    {
        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [
                ['key'=>$this->permission->key, 'value'=>1]
            ],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => []
        ])->getData();

        $this->assertTrue($user->hasPermission($this->permission->key));
        $this->assertTrue($user->hasAnyPermission([$this->permission->key]));

        // remove
        $user->removePermission($this->permission);

        $this->assertFalse($user->hasPermission($this->permission->key));
        $this->assertFalse($user->hasAnyPermission([$this->permission->key]));
    }

    public function test_user_can_inherit_to_group_permission()
    {
        $this->group->addPermission($this->permission->id,Group::PERMISSION_ALLOW);

        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => [
                $this->group->id => true
            ]
        ])->getData();

        $this->assertTrue($user->hasPermission($this->permission->key));
        $this->assertTrue($user->hasAnyPermission([$this->permission->key]));
    }

    public function test_user_special_permission_has_higher_priority_than_group_inherited_permission()
    {
        $group = factory(Group::class)->create();
        $group->addPermission($this->permission->id,Group::PERMISSION_ALLOW);

        $permission = factory(Permission::class)->create();

        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [
                ['key'=>$permission->key, 'value'=>-1]
            ],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => [
                $group->id => true
            ]
        ])->getData();

        $this->assertFalse($user->hasPermission($permission->key));
        $this->assertFalse($user->hasAnyPermission([$permission->key]));
    }

    public function test_can_add_permission_to_group()
    {
        $permission = factory(Permission::class)->create();

        $group = factory(Group::class)->create();
        $group->addPermission($permission->id,Group::PERMISSION_ALLOW);

        $this->assertTrue($group->hasPermission($permission->key));
    }

    public function test_can_remove_permission_to_group()
    {
        $permission = factory(Permission::class)->create();

        $group = factory(Group::class)->create();
        $group->addPermission($permission->id,Group::PERMISSION_ALLOW);

        // verify first permission was added
        $this->assertTrue($group->hasPermission($permission->key));

        // now lets remove the permission
        $group->removePermission($permission->id);

        $this->assertFalse($group->hasPermission($this->permission->key));
    }

    public function test_user_have_permissions_from_group_and_special_permission()
    {
        $permission = factory(Permission::class)->create();
        $permission2 = factory(Permission::class)->create();

        $group = factory(Group::class)->create();
        $group->addPermission($permission->id,Group::PERMISSION_ALLOW);

        $user = $this->userRepo->create([
            'name' => 'john',
            'email' => 'john@gmail.com',
            'password' => '12345678', // hash on the fly
            'permissions' => [
                ['key'=> $permission2->key, 'value'=> User::PERMISSION_ALLOW]
            ],
            'active' => null,
            'activation_key' => (Uuid::uuid4())->toString(),
            'groups' => [
                $group->id => true
            ]
        ])->getData();

        $this->assertTrue($user->hasPermission($permission->key));
        $this->assertTrue($user->hasPermission($permission2->key));
    }
}