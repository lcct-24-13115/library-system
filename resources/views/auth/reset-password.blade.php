<x-auth-poster title="Reset password">
    <h1 class="card-title">Choose a new password</h1>
    <p class="lf-hint">You are verified. Choose a new password for your library account.</p>

    <form method="POST" action="{{ route('password.store') }}" class="login-form">
        @csrf

        <div class="lf-field">
            <label for="password">New password</label>
            <div class="pw-wrap">
                <input id="password" name="password" type="password" required autofocus autocomplete="new-password" placeholder="At least 8 characters">
                <button type="button" class="pw-toggle" data-target="password" aria-pressed="false">Show</button>
            </div>
            @error('password')
                <p class="err">{{ $message }}</p>
            @enderror
        </div>

        <div class="lf-field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="pw-wrap">
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Type it again">
                <button type="button" class="pw-toggle" data-target="password_confirmation" aria-pressed="false">Show</button>
            </div>
        </div>

        <button type="submit" class="lf-submit">Reset password</button>
    </form>

    <p class="login-links"><a href="{{ route('login') }}">&larr; Back to sign in</a></p>

    <script>
        document.querySelectorAll('.pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Hide' : 'Show';
                btn.setAttribute('aria-pressed', show);
            });
        });
    </script>
</x-auth-poster>
