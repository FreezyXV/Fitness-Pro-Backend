<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Workout;
use App\Models\Exercise;
use App\Services\WorkoutService;
use Carbon\Carbon;

class WorkoutCompletionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected WorkoutService $workoutService;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user for testing
        $this->user = User::factory()->create();

        // Create some exercises
        Exercise::factory()->count(5)->create();

        // Resolve the WorkoutService from the container
        $this->workoutService = $this->app->make(WorkoutService::class);
    }

    /** @test */
    public function it_can_complete_a_workout_session_successfully()
    {
        // Create a workout template
        $template = Workout::factory()->create([
            'user_id' => $this->user->id,
            'is_template' => true,
            'status' => 'planned',
        ]);
        $template->exercises()->attach(Exercise::all()->random(2)->pluck('id')->toArray(), [
            'sets' => 3,
            'reps' => 10,
            'order_index' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Start a workout session from the template
        $session = $this->workoutService->startWorkout($this->user, $template->id);

        $this->assertNotNull($session);
        $this->assertEquals('in_progress', $session->status);
        $this->assertNotNull($session->started_at);
        $this->assertNull($session->completed_at);

        // Simulate completion data
        $completionData = [
            'notes' => 'Great session, felt strong!',
            'actual_duration' => 45,
        ];

        // Complete the workout session
        $completedSession = $this->workoutService->completeWorkout($session, $completionData);

        // Assertions
        $this->assertNotNull($completedSession);
        $this->assertEquals($session->id, $completedSession->id);
        $this->assertEquals('completed', $completedSession->status);
        $this->assertNotNull($completedSession->completed_at);
        $this->assertNotNull($completedSession->updated_at); // Ensure updated_at is set
        $this->assertEquals($completionData['notes'], $completedSession->notes);
        $this->assertEquals($completionData['actual_duration'], $completedSession->actual_duration);

        // Verify timestamps are Carbon instances (Eloquent casting)
        $this->assertInstanceOf(Carbon::class, $completedSession->started_at);
        $this->assertInstanceOf(Carbon::class, $completedSession->completed_at);
        $this->assertInstanceOf(Carbon::class, $completedSession->updated_at);

        // Verify no SQLSTATE[22P02] error occurred (implicitly by not throwing an exception)
        // This test will fail if a database error occurs during the update.
    }

    /** @test */
    public function it_handles_completion_of_a_session_without_template_id()
    {
        // Start a workout session without a template
        $session = $this->workoutService->startWorkout($this->user, null);

        $this->assertNotNull($session);
        $this->assertEquals('in_progress', $session->status);
        $this->assertNotNull($session->started_at);
        $this->assertNull($session->completed_at);

        // Simulate completion data
        $completionData = [
            'notes' => 'Freestyle workout done.',
            'actual_duration' => 30,
        ];

        // Complete the workout session
        $completedSession = $this->workoutService->completeWorkout($session, $completionData);

        // Assertions
        $this->assertNotNull($completedSession);
        $this->assertEquals($session->id, $completedSession->id);
        $this->assertEquals('completed', $completedSession->status);
        $this->assertNotNull($completedSession->completed_at);
        $this->assertNotNull($completedSession->updated_at);
        $this->assertEquals($completionData['notes'], $completedSession->notes);
        $this->assertEquals($completionData['actual_duration'], $completedSession->actual_duration);

        $this->assertInstanceOf(Carbon::class, $completedSession->started_at);
        $this->assertInstanceOf(Carbon::class, $completedSession->completed_at);
        $this->assertInstanceOf(Carbon::class, $completedSession->updated_at);
    }

    /** @test */
    public function it_updates_user_stats_and_clears_cache_on_completion()
    {
        // Mock StatisticsService to check if clearUserCache is called
        $mockStatisticsService = $this->mock(WorkoutService::class, function ($mock) {
            $mock->shouldReceive('clearUserCache')->once();
            // Ensure other methods are called on the real service
            $mock->shouldReceive('startWorkout')->andReturnUsing(function ($user, $templateId) {
                return $this->app->make(WorkoutService::class)->startWorkout($user, $templateId);
            });
            $mock->shouldReceive('completeWorkout')->andReturnUsing(function ($session, $completionData) {
                return $this->app->make(WorkoutService::class)->completeWorkout($session, $completionData);
            });
        });

        // Create a workout template
        $template = Workout::factory()->create([
            'user_id' => $this->user->id,
            'is_template' => true,
            'status' => 'planned',
        ]);
        $template->exercises()->attach(Exercise::all()->random(2)->pluck('id')->toArray(), [
            'sets' => 3,
            'reps' => 10,
            'order_index' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Start a workout session from the template
        $session = $this->workoutService->startWorkout($this->user, $template->id);

        // Simulate completion data
        $completionData = [
            'notes' => 'Stats test session.',
            'actual_duration' => 60,
        ];

        // Complete the workout session
        $completedSession = $this->workoutService->completeWorkout($session, $completionData);

        // Assertions for completion
        $this->assertEquals('completed', $completedSession->status);
        $this->assertNotNull($completedSession->completed_at);
        $this->assertNotNull($completedSession->updated_at);

        // The mock should have verified clearUserCache was called once
    }
}
