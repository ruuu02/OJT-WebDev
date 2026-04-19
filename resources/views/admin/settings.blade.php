<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

<script>
    // Force a fresh request when browser restores this page from bfcache.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) window.location.reload();
    });
</script>

<header class="sticky top-0 z-40 border-b border-blue-700 bg-blue-600/95 text-white backdrop-blur">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.visual-editor') }}"
                class="inline-flex items-center gap-2 rounded-md border border-white/35 bg-white/10 px-3 py-1.5 text-sm font-medium hover:bg-white/20 transition"
            >
                <span aria-hidden="true">&larr;</span>
                <span>Back to Visual Editor</span>
            </a>
            <span class="hidden text-xs uppercase tracking-widest text-blue-100 sm:inline">Security</span>
        </div>

        <div class="flex items-center gap-3">
            <span class="hidden text-sm text-blue-100 sm:inline">{{ session('admin_email') }}</span>
            <form method="POST" action="{{ route('admin.logout') }}" onsubmit="return confirmLogout(event)">
                @csrf
                <button
                    type="submit"
                    class="rounded-md border border-red-300/70 bg-red-500/20 px-3 py-1.5 text-sm font-medium text-red-100 hover:bg-red-500/35 transition"
                >
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your admin account credentials, activity history, and security posture.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @php
        $securityHighlights = [
            ['label' => 'Failed logins in 7 days', 'value' => $failedLogins7d ?? 0, 'tone' => ($failedLogins7d ?? 0) > 0 ? 'text-amber-700' : 'text-emerald-700'],
            ['label' => 'Last successful login', 'value' => $lastSuccessfulLogin ? $lastSuccessfulLogin->created_at->format('M j, Y g:i A') : 'No record yet', 'tone' => 'text-slate-900'],
            ['label' => 'Last password change', 'value' => $lastPasswordChange ? $lastPasswordChange->created_at->format('M j, Y g:i A') : 'No record yet', 'tone' => 'text-slate-900'],
        ];

        $securityChecklist = [
            'Use a unique password that is not used on any other account.',
            'Review failed login attempts after suspicious activity.',
            'Sign out after working on shared or public devices.',
            'Keep admin access limited to trusted accounts only.',
        ];
    @endphp

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Security overview</h2>
                <p class="mt-1 text-sm text-slate-500">A quick snapshot of recent activity and the basics that matter most.</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <div class="font-medium text-slate-900">{{ $admin->name }}</div>
                <div class="break-all">{{ $admin->email }}</div>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            @foreach ($securityHighlights as $highlight)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-widest text-slate-500">{{ $highlight['label'] }}</div>
                    <div class="mt-2 text-lg font-semibold {{ $highlight['tone'] }}">{{ $highlight['value'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[2fr,1fr] lg:items-start">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-slate-900">Change Account Details</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Update your name, email, or password. After saving, you will be logged out and must sign in again.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.account') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
                    <input
                        type="text"
                        name="name"
                        required
                        autocomplete="name"
                        value="{{ old('name', $admin->name) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('name') border-red-400 @enderror"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        value="{{ old('email', $admin->email) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('email') border-red-400 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('current_password') border-red-400 @enderror"
                    >
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">New Password</label>
                    <input
                        type="password"
                        name="new_password"
                        autocomplete="new-password"
                        placeholder="Leave blank to keep your current password"
                        id="newPassword"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        oninput="checkStrength(this.value)"
                    >

                    <ul class="mt-3 space-y-1.5 text-xs text-slate-500" id="requirements">
                        <li id="req-length" data-label="At least 12 characters">X At least 12 characters</li>
                        <li id="req-upper" data-label="One uppercase letter (A-Z)">X One uppercase letter (A-Z)</li>
                        <li id="req-lower" data-label="One lowercase letter (a-z)">X One lowercase letter (a-z)</li>
                        <li id="req-number" data-label="One number (0-9)">X One number (0-9)</li>
                        <li id="req-special" data-label="One special character (@$!%*#?&^_-)">X One special character (@$!%*#?&^_-)</li>
                    </ul>
                    <p class="mt-2 text-xs text-slate-500">Only fill this in if you want to change the password.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input
                        type="password"
                        name="new_password_confirmation"
                        required
                        autocomplete="new-password"
                        id="confirmPassword"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        oninput="checkMatch()"
                    >
                    <p id="matchMsg" class="mt-1 text-xs hidden"></p>
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        id="submitBtn"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </section>

        <div class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <h2 class="text-lg font-semibold text-slate-900">Account</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Name</dt>
                        <dd class="font-medium text-slate-800">{{ $admin->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="break-all font-medium text-slate-800">{{ $admin->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Account created</dt>
                        <dd class="font-medium text-slate-800">{{ $admin->created_at ? $admin->created_at->format('M j, Y') : 'Not available' }}</dd>
                    </div>
                </dl>
            </aside>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <h2 class="text-lg font-semibold text-slate-900">Security checklist</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    @foreach ($securityChecklist as $item)
                        <li class="flex gap-3">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </aside>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">Recent security activity</h2>
                    <span class="text-xs uppercase tracking-widest text-slate-400">Latest 8</span>
                </div>

                <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                    @forelse ($securityEvents as $event)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">
                                        @if ($event->event === 'login_success')
                                            Login successful
                                        @elseif ($event->event === 'login_failed')
                                            Failed login attempt
                                        @elseif ($event->event === 'password_changed')
                                            Password updated
                                        @else
                                            {{ str_replace('_', ' ', ucfirst($event->event)) }}
                                        @endif
                                    </div>
                                    <div class="mt-0.5 text-sm text-slate-600">{{ $event->description }}</div>
                                </div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>{{ $event->created_at->format('M j') }}</div>
                                    <div>{{ $event->created_at->format('g:i A') }}</div>
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                @if ($event->ip_address)
                                    <span class="rounded-full bg-white px-2.5 py-1 border border-slate-200">IP {{ $event->ip_address }}</span>
                                @endif
                                @if ($event->user_agent)
                                    <span class="rounded-full bg-white px-2.5 py-1 border border-slate-200">{{ \Illuminate\Support\Str::limit($event->user_agent, 36) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            No security events recorded yet.
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</main>

<script>
    function confirmLogout(event) {
        if (!window.confirm('Are you sure you want to log out?')) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    function setRequirement(id, pass) {
        const el = document.getElementById(id);
        if (!el) return;
        const label = el.dataset.label || '';
        el.textContent = (pass ? 'OK ' : 'X ') + label;
        el.className = pass ? 'text-emerald-600' : 'text-slate-500';
    }

    function checkStrength(val) {
        setRequirement('req-length',  val.length >= 12);
        setRequirement('req-upper',   /[A-Z]/.test(val));
        setRequirement('req-lower',   /[a-z]/.test(val));
        setRequirement('req-number',  /[0-9]/.test(val));
        setRequirement('req-special', /[@$!%*#?&^_\-]/.test(val));
        checkMatch();
    }

    function checkMatch() {
        const np = document.getElementById('newPassword').value;
        const cp = document.getElementById('confirmPassword').value;
        const msg = document.getElementById('matchMsg');
        if (!cp) {
            msg.classList.add('hidden');
            return;
        }
        if (np === cp) {
            msg.textContent = 'OK Passwords match';
            msg.className = 'mt-1 text-xs text-emerald-600';
        } else {
            msg.textContent = 'X Passwords do not match';
            msg.className = 'mt-1 text-xs text-red-500';
        }
        msg.classList.remove('hidden');
    }
</script>

</body>
</html>
