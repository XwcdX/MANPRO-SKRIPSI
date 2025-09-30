<?php

use App\Models\Student;
use App\Models\Lecturer;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case Setup
|--------------------------------------------------------------------------
|
| The `RefreshDatabase` trait will migrate your test database before
| each test and truncate it afterwards, ensuring a clean state.
| This is crucial for authentication tests.
|
*/
uses(RefreshDatabase::class, \Tests\TestCase::class);
/*
|--------------------------------------------------------------------------
| Authentication Tests for Students
|--------------------------------------------------------------------------
*/

test('a student can log in and access a protected route', function () {
    /** @var \Tests\TestCase $this */
    $student = Student::factory()->create([
        'email' => 'student@example.com',
        'password' => bcrypt('password'),
    ]);
    $response = $this->post('/login', [
        'email' => 'student@example.com',
        'password' => 'password',
        'guard' => 'student',
    ]);

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($student, 'student');

    $this->actingAs($student, 'student')
        ->get('/student/profile')
        ->assertOk()
        ->assertSee('Student Profile');
});


test('an unauthenticated user cannot access a student protected route', function () {
    /** @var \Tests\TestCase $this */
    $this->get('/student/profile')
        ->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| Authentication Tests for Lecturers
|--------------------------------------------------------------------------
*/

test('a lecturer can log in and access a protected route', function () {
    /** @var \Tests\TestCase $this */
    $lecturer = Lecturer::factory()->create([
        'email' => 'lecturer@example.com',
        'password' => bcrypt('password'),
    ]);
    $response = $this->post('/login', [
        'email' => 'lecturer@example.com',
        'password' => 'password',
        'guard' => 'lecturer',
    ]);

    $response->assertRedirect('/lecturer/dashboard');

    $this->assertAuthenticatedAs($lecturer, 'lecturer');

    $this->actingAs($lecturer, 'lecturer')
        ->get('/lecturer/courses')
        ->assertOk()
        ->assertSee('Lecturer Courses');
});

test('an unauthenticated user cannot access a lecturer protected route', function () {
    /** @var \Tests\TestCase $this */
    $this->get('/lecturer/courses')
        ->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| Testing your Custom Guard (if it's a middleware)
|--------------------------------------------------------------------------
|
| If your "own guard" is implemented as a middleware, you can test its
| behavior by attempting to access routes that use it, both with
| and without the expected permissions/roles.
|
*/

test('a student with required permissions can access a route protected by custom guard', function () {
    /** @var \Tests\TestCase $this */
    $student = Student::factory()->create([
        'email' => 'authorized@example.com',
        'is_active' => true,
        'has_permission_X' => true,
    ]);

    $this->actingAs($student, 'student')
        ->get('/student/restricted-area')
        ->assertOk()
        ->assertSee('Welcome to the Restricted Area');
});

test('a student without required permissions cannot access a route protected by custom guard', function () {
    /** @var \Tests\TestCase $this */
    $student = Student::factory()->create([
        'email' => 'unauthorized@example.com',
        'is_active' => false,
        'has_permission_X' => false,
    ]);

    $this->actingAs($student, 'student')
        ->get('/student/restricted-area')
        ->assertForbidden()
        ->assertSee('Unauthorized');
});

test('a lecturer cannot access a student restricted area protected by custom guard', function () {
    /** @var \Tests\TestCase $this */
    $lecturer = Lecturer::factory()->create();

    $this->actingAs($lecturer, 'lecturer')
        ->get('/student/restricted-area')
        ->assertForbidden();
});