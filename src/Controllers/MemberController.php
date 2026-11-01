<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Validator;
use Clubmanager\Core\Auth;
use Clubmanager\Models\Member;
use Clubmanager\Repositories\MemberRepository;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\ClubRepository;
use Clubmanager\Services\GdprService;

/**
 * Member Management Controller
 *
 * Implements CRUD actions, multi-faceted filtering, GDPR data exports,
 * and fee class assignments for club members.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class MemberController extends Controller
{
    private const MEMBER_FIELDS = [
        'salutation', 'sex', 'first_name', 'last_name', 'birth_date',
        'email', 'phone', 'street', 'zip', 'city', 'country',
        'membership_type', 'status', 'join_date', 'leave_date', 'invoice_marker',
        'bank_holder', 'bank_name', 'bank_bic', 'bank_iban',
    ];
    protected MemberRepository $memberRepo;
    protected FeeClassRepository $feeClassRepo;
    protected ClubRepository $clubRepo;
    protected GdprService $gdprService;

    public function __construct()
    {
        $this->memberRepo = new MemberRepository();
        $this->feeClassRepo = new FeeClassRepository();
        $this->clubRepo = new ClubRepository();
        $this->gdprService = new GdprService();
    }

    /**
     * Display members list with search and filtering.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'fee_class_id' => $request->query('fee_class_id'),
        ];

        $page = max(1, (int) $request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalMembers = $this->memberRepo->countSearch($filters);
        $members = $this->memberRepo->search($filters, $limit, $offset);
        $feeClasses = $this->feeClassRepo->getAll();

        $totalPages = (int) ceil($totalMembers / $limit);

        $this->render('members/index', [
            'members' => $members,
            'filters' => $filters,
            'feeClasses' => $feeClasses,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalMembers' => $totalMembers,
        ]);
    }

    /**
     * Show create member form.
     *
     * @param Request $request
     * @return void
     */
    public function create(Request $request): void
    {
        $feeClasses = $this->feeClassRepo->getActive();
        $this->render('members/create', [
            'feeClasses' => $feeClasses,
        ]);
    }

    /**
     * Store new member.
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

        // Validation
        $validator = Validator::make($data, [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'birth_date' => 'required|date',
            'join_date' => 'required|date',
            'membership_type' => 'required',
            'status' => 'required',
        ]);

        if (!empty($data['email'])) {
            $validator = Validator::make($data, ['email' => 'email|max:255']);
        }

        if (!empty($data['bank_iban'])) {
            $validator = Validator::make($data, ['bank_iban' => 'iban']);
        }

        if ($validator->fails()) {
            $this->flash('danger', $validator->first('first_name') ?: __('validation_error'));
            $this->redirect('/members/create');
            return;
        }

        // Clean values
        $data['invoice_marker'] = !empty($data['invoice_marker']) ? 1 : 0;
        if (isset($data['leave_date']) && $data['leave_date'] === '') {
            $data['leave_date'] = null;
        }
        if (!empty($data['bank_iban'])) {
            $data['bank_iban'] = strtoupper(preg_replace('/\s+/', '', (string)$data['bank_iban']));
        }
        if (!empty($data['bank_bic'])) {
            $data['bank_bic'] = strtoupper(trim((string)$data['bank_bic']));
        }

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $memberData = array_intersect_key($data, array_flip(self::MEMBER_FIELDS));
        $memberData['club_id'] = (int) $this->clubRepo->getPrimary()->Id;
        $member = $this->memberRepo->create($memberData, $userId);

        // Assign initial fee class if selected
        if (!empty($data['initial_fee_class_id'])) {
            $this->feeClassRepo->assignToMember(
                (int) $member->id,
                (int) $data['initial_fee_class_id'],
                null,
                $member->join_date,
                null,
                $userId
            );
        }

        $this->flash('success', __('member_saved'));
        $this->redirect('/members/' . $member->id);
    }

    /**
     * Display member profile details.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function show(Request $request, int|string $id): void
    {
        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->flash('danger', __('members_none_found'));
            $this->redirect('/members');
            return;
        }

        $assignedFees = $member->getActiveFeeClasses();
        $allFeeClasses = $this->feeClassRepo->getActive();
        $feeHistory = (new \Clubmanager\Repositories\MembershipFeeRepository())->getForMember((int) $member->id);
        $auditLogs = (new \Clubmanager\Repositories\AuditLogRepository())->search([
            'entity_type' => 'members',
            'entity_id' => (int) $member->id,
        ], 20);

        $this->render('members/show', [
            'member' => $member,
            'assignedFees' => $assignedFees,
            'allFeeClasses' => $allFeeClasses,
            'feeHistory' => $feeHistory,
            'auditLogs' => $auditLogs,
        ]);
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
        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->flash('danger', __('members_none_found'));
            $this->redirect('/members');
            return;
        }

        $feeClasses = $this->feeClassRepo->getAll();

        $this->render('members/edit', [
            'member' => $member,
            'feeClasses' => $feeClasses,
            'assignedFees' => $member->getActiveFeeClasses(),
            'departments' => (new \Clubmanager\Repositories\DepartmentRepository())->getAll(),
            'assignedDepartments' => (new \Clubmanager\Repositories\DepartmentRepository())->getForMember((int) $member->id),
        ]);
    }

    /**
     * Update member.
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

        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->flash('danger', __('members_none_found'));
            $this->redirect('/members');
            return;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'birth_date' => 'required|date',
            'join_date' => 'required|date',
        ]);

        if (!empty($data['bank_iban'])) {
            $validator = Validator::make($data, ['bank_iban' => 'iban']);
        }

        if ($validator->fails()) {
            $this->flash('danger', $validator->first('first_name') ?: __('validation_error'));
            $this->redirect('/members/' . $id . '/edit');
            return;
        }

        $data['invoice_marker'] = !empty($data['invoice_marker']) ? 1 : 0;
        if (isset($data['leave_date']) && $data['leave_date'] === '') {
            $data['leave_date'] = null;
        }
        if (!empty($data['bank_iban'])) {
            $data['bank_iban'] = strtoupper(preg_replace('/\s+/', '', (string)$data['bank_iban']));
        }
        if (!empty($data['bank_bic'])) {
            $data['bank_bic'] = strtoupper(trim((string)$data['bank_bic']));
        }

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->memberRepo->update($member, array_intersect_key($data, array_flip(self::MEMBER_FIELDS)), $userId);

        $this->flash('success', __('member_updated'));
        $this->redirect('/members/' . $id);
    }

    /**
     * Archive a member (set status to Inaktiv).
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function archive(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $member = $this->memberRepo->findById((int) $id);
        if ($member) {
            $userId = Auth::user() ? (int) Auth::user()->id : null;
            $this->memberRepo->archive($member, $userId);
            $this->flash('info', __('member_archived'));
        }

        $this->redirect('/members/' . $id);
    }

    /**
     * Permanently delete a member.
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

        $member = $this->memberRepo->findById((int) $id);
        if ($member) {
            $userId = Auth::user() ? (int) Auth::user()->id : null;
            $deleted = $this->memberRepo->delete($member, $userId);
            $this->flash(
                $deleted ? 'success' : 'danger',
                __($deleted ? 'member_deleted' : 'member_delete_blocked')
            );
        }

        $this->redirect('/members');
    }

    /**
     * Assign a fee class to member.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function assignFeeClass(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->redirect('/members');
            return;
        }

        $feeClassId = (int) $request->post('fee_class_id');
        $customAmount = $request->post('custom_amount') !== '' ? (float) $request->post('custom_amount') : null;
        $startDate = $request->post('start_date') ?: date('Y-m-d');
        $endDate = $request->post('end_date') ?: null;

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->feeClassRepo->assignToMember((int) $member->id, $feeClassId, $customAmount, $startDate, $endDate, $userId);

        $this->flash('success', __('fee_class_saved'));
        $this->redirect('/members/' . $id);
    }

    /**
     * Update one fee class assignment.
     */
    public function updateFeeClass(Request $request, int|string $id, int|string $assignmentId): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->flash('danger', __('members_none_found'));
            $this->redirect('/members');
            return;
        }

        $feeClassId = filter_var($request->post('fee_class_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = Auth::user() ? (int) Auth::user()->id : null;
        if ($feeClassId === false || !$this->feeClassRepo->findById($feeClassId)
            || !$this->feeClassRepo->updateAssignment((int) $member->id, (int) $assignmentId, $feeClassId, $userId)) {
            $this->flash('danger', __('validation_error'));
        } else {
            $this->flash('success', __('fee_class_saved'));
        }

        $this->redirect('/members/' . $id . '/edit');
    }

    /**
     * Remove fee class assignment from member.
     *
     * @param Request $request
     * @param int|string $id
     * @param int|string $assignmentId
     * @return void
     */
    public function removeFeeClass(Request $request, int|string $id, int|string $assignmentId): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->feeClassRepo->removeAssignment((int) $assignmentId, $userId);

        $this->flash('info', __('fee_class_deleted'));
        $this->redirect('/members/' . $id);
    }

    /**
     * GDPR JSON export.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function exportGdprJson(Request $request, int|string $id): void
    {
        $member = $this->memberRepo->findById((int) $id);
        if (!$member) {
            $this->redirect('/members');
            return;
        }
        $this->gdprService->exportJson($member);
    }

    /**
     * GDPR PDF export.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function exportGdprPdf(Request $request, int|string $id): void
    {
        $member = $this->memberRepo->findById((int) $id);
        $club = $this->clubRepo->getPrimary();
        if (!$member) {
            $this->redirect('/members');
            return;
        }
        $this->gdprService->exportPdf($member, $club);
    }

    /**
     * GDPR anonymize personal data.
     *
     * @param Request $request
     * @param int|string $id
     * @return void
     */
    public function anonymize(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $member = $this->memberRepo->findById((int) $id);
        if ($member) {
            $userId = Auth::user() ? (int) Auth::user()->id : null;
            $this->gdprService->anonymize($member, $userId);
            $this->flash('warning', __('member_anonymized'));
        }

        $this->redirect('/members/' . $id);
    }
}
