<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('All Waste Posts') }}
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

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
                    <form method="GET" action="{{ route('admin.waste-posts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Title or Donor...">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Available</option>
                                <option value="taken" {{ request('status') === 'taken' ? 'selected' : '' }}>Assigned</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Collected</option>
                                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Waste Type</label>
                            <select name="category" id="category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Types</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Location Filter -->
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input type="text" name="location" id="location" value="{{ request('location') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Search location...">
                        </div>

                        <!-- From Date -->
                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- To Date -->
                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex items-end gap-2 md:col-span-2">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                            <a href="{{ route('admin.waste-posts.index') }}"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Waste Posts Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            All Waste Posts ({{ $wastePosts->total() }})
                        </h3>
                    </div>

                    @if ($wastePosts->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg">No waste posts found</p>
                            <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Donor</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Waste Type</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Posted</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($wastePosts as $post)
                                        <tr>
                                            <!-- ID -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #{{ $post->id }}
                                            </td>

                                            <!-- Title -->
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ Str::limit($post->title, 30) }}</div>
                                                <div class="text-xs text-gray-500">{{ Str::limit($post->description, 40) }}</div>
                                            </td>

                                            <!-- Donor -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $post->user->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $post->user->email }}</div>
                                            </td>

                                            <!-- Waste Type -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                    {{ ucfirst($post->category) }}
                                                </span>
                                            </td>

                                            <!-- Quantity -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $post->quantity ?? 'N/A' }}
                                            </td>

                                            <!-- Location -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ Str::limit($post->location, 20) }}
                                            </td>

                                            <!-- Status -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($post->isOpen())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        🟢 Available
                                                    </span>
                                                @elseif ($post->isTaken())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        🟡 Assigned
                                                    </span>
                                                @elseif ($post->isCompleted())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        🔵 Collected
                                                    </span>
                                                @elseif ($post->isExpired())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        ⚪ Expired
                                                    </span>
                                                @elseif ($post->isCancelled())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        🔴 Cancelled
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Posted -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $post->created_at->format('M d, Y') }}
                                                <div class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</div>
                                            </td>

                                            <!-- Assigned To -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if ($post->latestJob && $post->latestJob->collector)
                                                    <div class="text-sm text-gray-900">{{ $post->latestJob->collector->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $post->latestJob->collector->phone }}</div>
                                                @else
                                                    <span class="text-gray-400">Not assigned</span>
                                                @endif
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex flex-col gap-2">
                                                    <!-- View Details -->
                                                    <a href="{{ route('admin.waste-posts.show', $post) }}"
                                                        class="text-blue-600 hover:text-blue-900 font-medium">
                                                        View Details
                                                    </a>

                                                    <!-- Force Assign (only for available posts) -->
                                                    @if ($post->isOpen())
                                                        <button onclick="openAssignModal({{ $post->id }}, '{{ addslashes($post->title) }}')"
                                                            class="text-green-600 hover:text-green-900 font-medium text-left">
                                                            Force Assign
                                                        </button>
                                                    @endif

                                                    <!-- Delete -->
                                                    <form action="{{ route('admin.waste-posts.destroy', $post) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            onclick="return confirm('Delete this waste post? This action cannot be undone.')"
                                                            class="text-red-600 hover:text-red-900 font-medium">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $wastePosts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Collector Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Force Assign Collector</h3>
            
            <form id="assignForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="collector_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Collector
                    </label>
                    <select 
                        id="collector_id" 
                        name="collector_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                        <option value="">Loading collectors...</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select an active collector to assign this waste post</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAssignModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                        Assign Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let activeCollectors = [];

        // Fetch active collectors on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchActiveCollectors();
        });

        function fetchActiveCollectors() {
            // For now, we'll populate with a simple option
            // In production, this would be an API call
            const select = document.getElementById('collector_id');
            select.innerHTML = '<option value="">Select a collector...</option>';
        }

        function openAssignModal(postId, postTitle) {
            const modal = document.getElementById('assignModal');
            const form = document.getElementById('assignForm');
            
            form.action = `/admin/waste-posts/${postId}/assign`;
            modal.querySelector('h3').textContent = `Assign: ${postTitle}`;
            
            modal.classList.remove('hidden');
        }

        function closeAssignModal() {
            const modal = document.getElementById('assignModal');
            modal.classList.add('hidden');
            document.getElementById('assignForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAssignModal();
            }
        });
    </script>
</x-app-layout>
