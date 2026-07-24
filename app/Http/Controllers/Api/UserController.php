<?php

namespace App\Http\Controllers\Api;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\UserManagementError;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
  public function __construct(
    protected UserService $userService,
    protected ErrorDefinitionReader $reader,
  ) {}

  /**
   * Display a paginated list of users.
   *
   * @summary Mengambil daftar pengguna ter-paginasi dari server.
   */
  public function index(ListUserRequest $request): AnonymousResourceCollection
  {
    $users = $this->userService->getPaginatedUsers($request->validated());

    return UserResource::collection($users);
  }

  /**
   * Endpoint demonstrasi EDF ApplicationException.
   *
   * @summary Simulasi kegagalan bisnis (ApplicationException EDF).
   */
  public function testError(): never
  {
    throw new ApplicationException(
      definition: $this->reader->read(UserManagementError::USER_LOCKED),
      context: ['user_id' => 99, 'action' => 'update_status'],
    );
  }
}
