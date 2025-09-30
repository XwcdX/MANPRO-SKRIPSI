<?php

use App\Models\Student;
use App\Models\Lecturer;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Authentication Tests for Students
|--------------------------------------------------------------------------
*/

test('a student can log in', function () {
    /** @var \Tests\TestCase $this*/
    $student = Student::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $nrp = explode('@', $student->email)[0];

    Livewire::test('auth.login')
        ->set('nrp', $nrp)
        ->set('password', 'password')
        ->set('role', 'student')
        ->call('login')
        ->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($student, 'student');
});


test('an unauthenticated user is redirected from the student dashboard', function () {
    /** @var \Tests\TestCase $this*/
    $this->get(route('student.dashboard'))
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Authentication Tests for Lecturers
|--------------------------------------------------------------------------
*/

test('a lecturer can log in', function () {
    /** @var \Tests\TestCase $this*/
    $lecturer = Lecturer::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $username = explode('@', $lecturer->email)[0];

    Livewire::test('auth.login')
        ->set('nrp', $username)
        ->set('password', 'password')
        ->set('role', 'lecturer')
        ->call('login')
        ->assertRedirect(route('lecturer.dashboard'));

    $this->assertAuthenticatedAs($lecturer, 'lecturer');
});

test('an unauthenticated user is redirected from the lecturer dashboard', function () {
    /** @var \Tests\TestCase $this*/
    $this->get(route('lecturer.dashboard'))
        ->assertRedirect(route('login'));
});