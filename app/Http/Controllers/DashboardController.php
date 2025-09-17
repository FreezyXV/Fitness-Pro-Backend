<?php
// app/Http/Controllers/DashboardController.php - SIMPLIFIED VERSION
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkoutSession; // Keep for now, might be removed later
use App\Models\Goal; // Keep for now, might be removed later
use App\Models\WorkoutPlan; // Keep for now, might be removed later
use App\Services\StatisticsService; // Added

class DashboardController extends BaseController
{
    protected StatisticsService $statisticsService; // Added

    public function __construct(StatisticsService $statisticsService) // Added constructor
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Get dashboard data - simplified without service dependencies
     */
    public function index()
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            
            \Log::info('Dashboard request for user: ' . $user->id);

            try {
                $dashboardData = $this->statisticsService->getDashboardStats($user);

                // Add user profile information for sidebar/dashboard display
                $dashboardData['user_profile'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'profile_photo_url' => $user->profile_photo_url,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ];

                \Log::info('Dashboard data prepared successfully');
                return $this->successResponse($dashboardData, 'Dashboard data loaded successfully');

            } catch (\Exception $e) {
                \Log::error('Dashboard error: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                
                return $this->errorResponse('Dashboard loading failed: ' . $e->getMessage(), 500);
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return $this->errorResponse('Dashboard loading failed: ' . $e->getMessage(), 500);
        }
    }
}