<?php

namespace Ums\Http\Controllers;

use App\Http\Controllers\Controller;
use Ums\Services\UserService;

/**
 * UMS Dashboard Controller
 */
class DashboardController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display the UMS dashboard.
     */
    public function index()
    {
        $statistics = $this->userService->getStatistics();
        $recentUsers = $this->userService->getRecentUsers(5);

        return view('ums::dashboard', compact('statistics', 'recentUsers'));
    }
}

