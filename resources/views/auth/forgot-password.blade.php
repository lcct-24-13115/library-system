<x-auth-poster title="Forgot password">
    <h1 class="card-title">Forgot your password?</h1>
    <p class="lf-hint">Students and faculty: enter your ID number, then choose a new password.</p>

    <form method="POST" action="{{ route('password.email') }}" class="login-form">
        @csrf

        <div class="lf-field">
            <label for="id_number">ID number</label>
            <input id="id_number" name="id_number" type="text" value="{{ old('id_number') }}"
                   placeholder="Your ID number" required autofocus autocomplete="off">
            @error('id_number')
                <p class="err">{{ $message }}</p>
            @enderror
        </div>


        <button type="submit" class="lf-submit">Continue</button>
    </form>

    <p class="lf-hint" style="margin:16px 0 0;font-size:13px">
        <strong>Library staff:</strong> please ask the library administrator to reset your password.
        Can't get in? See the librarian.
    </p>

    <p class="login-links"><a href="{{ route('login') }}">&larr; Back to sign in</a></p>
</x-auth-poster>
