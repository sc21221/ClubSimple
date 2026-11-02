<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Validator;
use Clubmanager\Core\Auth;
use Clubmanager\Models\User;
use Clubmanager\Repositories\UserRepository;

/**
 * User & Role Management Controller
 *
 * Implements CRUD actions for system administrators and staff accounts.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class UserController extends Controller
{
    protected UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    /**
     * List all users.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $users = $this->repo->getAll();
        $this->render('users/index', [
            'users' => $users,
        ]);
    }

    /**
     * Show create user form.
     *
     * @param Request $request
     * @return void
     */
    public function create(Request $request): void
    {
        $this->render('users/create');
    }

    /**
     * Store new user.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
        ]);

        if (!is_string($data['password'] ?? null) || mb_strlen($data['password']) < 8) {
            $this->flash('danger', __('user_password_min_length'));
            $this->redirect('/users/create');
            return;
        }

        if ($validator->fails() || !in_array($data['role'] ?? null, User::ROLES, true)) {
            $this->flash('danger', $validator->first('name') ?: __('validation_error'));
            $this->redirect('/users/create');
            return;
        }

        // Check if email already taken
        if ($this->repo->findByEmail((string)$data['email'])) {
            $this->flash('danger', 'Diese E-Mail-Adresse wird bereits verwendet.');
            $this->redirect('/users/create');
            return;
        }

        $authorId = Auth::user() ? (int) Auth::user()->id : null;
        $this->repo->create($data, $authorId);

        $this->flash('success', __('user_saved'));
        $this->redirect('/users');
    }

    /**
     * Show edit user form.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function edit(Request $request, int|string $id): void
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            $this->redirect('/users');
            return;
        }

        $this->render('users/edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update user.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function update(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $user = $this->repo->findById($id);
        if (!$user) {
            $this->redirect('/users');
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
        ]);

        $password = $data['password'] ?? '';
        if (!is_string($password) || ($password !== '' && mb_strlen($password) < 8)) {
            $this->flash('danger', __('user_password_min_length'));
            $this->redirect('/users/' . $id . '/edit');
            return;
        }

        if ($validator->fails() || !in_array($data['role'] ?? null, User::ROLES, true)) {
            $this->flash('danger', $validator->first('name') ?: __('validation_error'));
            $this->redirect('/users/' . $id . '/edit');
            return;
        }

        $authorId = Auth::user() ? (int) Auth::user()->id : null;
        $this->repo->update($user, $data, $authorId);

        $this->flash('success', __('user_updated'));
        $this->redirect('/users');
    }

    /**
     * Delete user.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function delete(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $currentUser = Auth::user();
        if ($currentUser && (int)$currentUser->id === (int)$id) {
            $this->flash('danger', __('cannot_delete_self'));
            $this->redirect('/users');
            return;
        }

        $user = $this->repo->findById($id);
        if ($user) {
            $authorId = $currentUser ? (int) $currentUser->id : null;
            $this->repo->delete($user, $authorId);
            $this->flash('info', __('user_deleted'));
        }

        $this->redirect('/users');
    }
}
