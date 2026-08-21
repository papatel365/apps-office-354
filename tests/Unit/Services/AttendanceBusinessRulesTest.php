<?php

namespace Tests\Unit\Services;

use App\Models\HRD\Attendance;
use App\Models\HRD\EmployeeProfile;
use App\Models\User;
use App\Models\Company;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected EmployeeProfile $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company
        $this->company = Company::create([
            'name' => 'Test Company',
            'uuid' => fake()->uuid(),
        ]);

        // Create user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        // Create employee profile
        $this->employee = EmployeeProfile::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'full_name' => 'Test Employee',
            'employee_number' => 'EMP001',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function check_in_allowed_when_no_attendance_exists(): void
    {
        $this->actingAs($this->user);

        // Verify no attendance exists for today
        $existing = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNull($existing);
    }

    /** @test */
    public function check_in_creates_record_with_correct_data(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia',
            'gps_accuracy' => 10.5,
        ]);

        $response->assertSuccessful();

        $attendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->check_in_time);
        $this->assertNull($attendance->check_out_time);
    }

    /** @test */
    public function second_check_in_is_rejected(): void
    {
        $this->actingAs($this->user);

        // First check-in
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // Second check-in attempt
        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'ALREADY_CHECKED_IN',
        ]);
    }

    /** @test */
    public function check_out_allowed_after_check_in(): void
    {
        $this->actingAs($this->user);

        // Check-in first
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // Then check-out
        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base51,/9j/4AAQSkZJRg==',
            'latitude' => -6.2089,
            'longitude' => 106.8457,
        ]);

        $response->assertSuccessful();

        $attendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance->check_out_time);
    }

    /** @test */
    public function second_check_out_is_rejected(): void
    {
        $this->actingAs($this->user);

        // Check-in
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // Check-out
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2089,
            'longitude' => 106.8457,
        ]);

        // Second check-out attempt
        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2090,
            'longitude' => 106.8458,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'ALREADY_CHECKED_OUT',
        ]);
    }

    /** @test */
    public function attendance_completed_state_blocks_further_actions(): void
    {
        $this->actingAs($this->user);

        // Check-in
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // Check-out
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2089,
            'longitude' => 106.8457,
        ]);

        // Try to check-in again
        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2090,
            'longitude' => 106.8458,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'ATTENDANCE_COMPLETED',
        ]);
    }

    /** @test */
    public function check_in_requires_first(): void
    {
        $this->actingAs($this->user);

        // Try to check-out without check-in
        $response = $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'NOT_CHECKED_IN',
        ]);
    }

    /** @test */
    public function same_employee_cannot_access_other_tenant_attendance(): void
    {
        // Create another company and employee
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'uuid' => fake()->uuid(),
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'company_id' => $otherCompany->id,
        ]);

        // Create attendance for other company
        $otherEmployee = EmployeeProfile::create([
            'company_id' => $otherCompany->id,
            'user_id' => $otherUser->id,
            'full_name' => 'Other Employee',
            'employee_number' => 'EMP002',
            'is_active' => true,
        ]);

        Attendance::create([
            'employee_id' => $otherEmployee->id,
            'company_id' => $otherCompany->id,
            'date' => now()->toDateString(),
            'check_in_time' => now(),
        ]);

        // Try to access with current user (should only see their own company's attendance)
        $response = $this->actingAs($this->user)
            ->getJson(route('hrd.attendances.index'));

        $response->assertSuccessful();

        // User A should not see User B's attendance
        $this->assertStringNotContainsString(
            'Other Employee',
            $response->getContent()
        );
    }

    /** @test */
    public function check_in_and_check_out_use_different_photos(): void
    {
        $this->actingAs($this->user);

        // Check-in
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,AAAAAATA==',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $checkInAttendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $checkInPhoto = $checkInAttendance->check_in_photo;

        // Check-out with different photo
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,BBBBBUTB==',
            'latitude' => -6.2089,
            'longitude' => 106.8457,
        ]);

        $checkOutAttendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        // Photos should be different (check-in photo should not be overwritten by check-out photo)
        $this->assertEquals($checkInPhoto, $checkInAttendance->fresh()->check_in_photo);
        $this->assertNotNull($checkInAttendance->fresh()->check_out_photo);
        $this->assertNotEquals($checkInAttendance->fresh()->check_in_photo, $checkInAttendance->fresh()->check_out_photo);
    }

    /** @test */
    public function gps_coordinates_are_captured_separately(): void
    {
        $this->actingAs($this->user);

        // Check-in
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_in',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.208800,
            'longitude' => 106.845600,
        ]);

        $attendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $checkInLat = $attendance->check_in_latitude;
        $checkInLng = $attendance->check_in_longitude;

        // Check-out with different GPS
        $this->postJson(route('hrd.face-attendance.submit'), [
            'type' => 'check_out',
            'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
            'latitude' => -6.209900,
            'longitude' => 106.846700,
        ]);

        $attendance->refresh();

        // GPS should be different for check-in and check-out
        $this->assertEquals(-6.208800, $checkInLat);
        $this->assertEquals(-6.209900, $attendance->check_out_latitude);
        $this->assertNotEquals($checkInLat, $attendance->check_out_latitude);
    }
}
