<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('All Users') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Filter Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">
                            All Users ({{ $users->total() }})
                        </a>
                        <a href="{{ route('admin.collectors.pending') }}" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-md text-sm hover:bg-yellow-200">
                            Pending Collectors
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                                    @if($user->phone)
                                                        <div class="text-xs text-gray-400">{{ $user->phone }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($user->isAdmin()) bg-purple-100 text-purple-800
                                                @elseif($user->isDonor()) bg-blue-100 text-blue-800
                                                @else bg-green-100 text-green-800 @endif">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($user->isActive()) bg-green-100 text-green-800
                                                @elseif($user->isPending()) bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->created_at->format('M d, Y') }}
                                            <div class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($user->isBlocked())
                                                <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Activate {{ $user->name }}?')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                        Activate
                                                    </button>
                                                </form>
                                            @elseif($user->isPending() && $user->isCollector())
                                                <form action="{{ route('admin.collectors.approve', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Approve {{ $user->name }} as collector?')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline ml-2">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Block {{ $user->name }}?')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                                        Block
                                                    </button>
                                                </form>
                                            @elseif(!$user->isAdmin())
                                                <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Block {{ $user->name }}? They will not be able to access the system.')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                                        Block
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">Admin</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
