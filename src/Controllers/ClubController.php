<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Validator;
use Clubmanager\Core\Auth;
use Clubmanager\Repositories\ClubRepository;

/**
 * Club Master Data Controller
 *
 * Implements maintenance of club headquarters, presidency, and SEPA creditor configuration.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class ClubController extends Controller
{
    protected ClubRepository $repo;

    public function __construct()
    {
        $this->repo = new ClubRepository();
    }

    /**
     * Display edit form for club master data.
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request): void
    {
        $club = $this->repo->getPrimary();
        $this->render('club/edit', [
            'club' => $club,
        ]);
    }

    /**
     * Update club master data and SEPA configuration.
     *
     * @param Request $request
     * @return void
     */
    public function update(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $club = $this->repo->getPrimary();
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:100',
            'bank_creditor_id' => 'required|max:32',
            'bank_iban' => 'required|iban',
            'bank_bic' => 'required|bic',
        ]);

        if ($validator->fails()) {
            $this->flash('danger', $validator->first('name') ?: __('validation_error'));
            $this->redirect('/club');
            return;
        }

        $data['bank_iban'] = strtoupper(preg_replace('/\s+/', '', (string)$data['bank_iban']));
        $data['bank_bic'] = strtoupper(trim((string)$data['bank_bic']));

        $userId = Auth::user() ? (int) Auth::user()->id : null;
        $this->repo->update($club, $data, $userId);

        $this->flash('success', __('club_updated'));
        $this->redirect('/club');
    }
}
