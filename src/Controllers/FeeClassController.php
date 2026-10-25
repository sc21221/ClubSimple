<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Validator;
use Clubmanager\Core\Auth;
use Clubmanager\Repositories\FeeClassRepository;

/**
 * Fee Class Management Controller
 *
 * Implements CRUD actions for fee categories and age boundaries.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class FeeClassController extends Controller
{
    protected FeeClassRepository $repo;

    public function __construct()
    {
        $this->repo = new FeeClassRepository();
    }

    /**
     * List all fee classes.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $feeClasses = $this->repo->getAll();
        $this->render('fee_classes/index', [
            'feeClasses' => $feeClasses,
        ]);
    }

    /**
     * Show create fee class form.
     *
     * @param Request $request
     * @return void
     */
    public function create(Request $request): void
    {
        $this->render('fee_classes/create');
    }

    /**
     * Store new fee class.
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
            'name' => 'required|max:100',
            'base_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required',
        ]);

        if ($validator->fails()) {
            $this->flash('danger', $validator->first('name') ?: __('validation_error'));
            $this->redirect('/fee-classes/create');
            return;
        }

        $data['is_active'] = !empty($data['is_active']) ? 1 : 0;
        $data['min_age'] = isset($data['min_age']) && $data['min_age'] !== '' ? (int)$data['min_age'] : 0;
        $data['max_age'] = isset($data['max_age']) && $data['max_age'] !== '' ? (int)$data['max_age'] : 999;
        $data['currency'] = 'EUR';

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->repo->create($data, $userId);

        $this->flash('success', __('fee_class_saved'));
        $this->redirect('/fee-classes');
    }

    /**
     * Show edit form.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function edit(Request $request, int|string $id): void
    {
        $feeClass = $this->repo->findById((int) $id);
        if (!$feeClass) {
            $this->redirect('/fee-classes');
            return;
        }

        $this->render('fee_classes/edit', [
            'feeClass' => $feeClass,
        ]);
    }

    /**
     * Update fee class.
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

        $feeClass = $this->repo->findById((int) $id);
        if (!$feeClass) {
            $this->redirect('/fee-classes');
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:100',
            'base_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required',
        ]);

        if ($validator->fails()) {
            $this->flash('danger', $validator->first('name') ?: __('validation_error'));
            $this->redirect('/fee-classes/' . $id . '/edit');
            return;
        }

        $data['is_active'] = !empty($data['is_active']) ? 1 : 0;
        $data['min_age'] = isset($data['min_age']) && $data['min_age'] !== '' ? (int)$data['min_age'] : 0;
        $data['max_age'] = isset($data['max_age']) && $data['max_age'] !== '' ? (int)$data['max_age'] : 999;

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->repo->update($feeClass, $data, $userId);

        $this->flash('success', __('fee_class_updated'));
        $this->redirect('/fee-classes');
    }

    /**
     * Delete fee class.
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

        $feeClass = $this->repo->findById((int) $id);
        if ($feeClass) {
            $userId = Auth::user() ? (int) Auth::user()->id : null;
            $this->repo->delete($feeClass, $userId);
            $this->flash('info', __('fee_class_deleted'));
        }

        $this->redirect('/fee-classes');
    }
}
