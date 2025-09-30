<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Login</title>
</head>
<body>
    <h1>Test Login Form</h1>
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ url('/test-login-action') }}">
        @csrf
        <label for="email">Full Email:</label><br>
        <input type="email" id="email" name="email" value="c14230074@john.petra.ac.id" required><br><br>
        
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="guard">Guard:</label><br>
        <select name="guard" id="guard">
            <option value="student">student</option>
            <option value="lecturer">lecturer</option>
        </select><br><br>

        <button type="submit">Log In</button>
    </form>
</body>
</html>