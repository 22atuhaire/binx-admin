<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Waste Post Details') }}
            </h2>
            <a href="{{ route('admin.waste-posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to All Posts
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Post Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <!-- Title & Status -->
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $wastePost->title }}</h3>
                                    <p class="text-sm text-gray-500">Post #{{ $wastePost->id }} • Posted {{ $wastePost->created_at->diffForHumans() }}</p>
                                </div>
                                <div>
                                    @if ($wastePost->isOpen())
                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            🟢 Available
                                        </span>
                                    @elseif ($wastePost->isTaken())
                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            🟡 Assigned
                                        </span>
                                    @elseif ($wastePost->isCompleted())
                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            🔵 Collected
                                        </span>
                                    @elseif ($wastePost->isExpired())
                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            ⚪ Expired
                                        </span>
                                    @elseif ($wastePost->isCancelled())
                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            🔴 Cancelled
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Description</h4>
                                <p class="text-gray-600 leading-relaxed">{{ $wastePost->description }}</p>
                            </div>

                            <!-- Post Details Grid -->
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Waste Type</p>
                                    <span class="inline-block mt-1 px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                                        {{ ucfirst($wastePost->category) }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Quantity</p>
                                    <p class="text-gray-900 mt-1">{{ $wastePost->quantity ?? 'Not specified' }}</p>
                                </div>

                                <div class="col-span-2">
                                    <p class="text-sm font-semibold text-gray-700">Location</p>
                                    <p class="text-gray-900 mt-1">📍 {{ $wastePost->location }}</p>
                                </div>
                            </div>

                            <!-- Image -->
                            @if ($wastePost->image_path)
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Waste Image</h4>
                                    <img src="{{ asset('storage/' . $wastePost->image_path) }}" 
                                        alt="Waste image" 
                                        class="w-full h-64 object-cover rounded-lg border border-gray-200">
                                </div>
                            @else
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Waste Image</h4>
                                    <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center border border-dashed border-gray-300">
                                        <p class="text-gray-400">No image provided</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Map Placeholder -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Location Map</h4>
                                <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-500 mt-2">Map integration coming soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collection Job History -->
                    @if ($wastePost->jobs->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection History</h3>
                                
                                <div class="space-y-4">
                                    @foreach ($wastePost->jobs as $job)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $job->collector->name }}</p>
                                                    <p class="text-sm text-gray-500">{{ $job->collector->phone }}</p>
                                                </div>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    @if ($job->status === 'pending') bg-yellow-100 text-yellow-800
                                                    @elseif ($job->status === 'in_progress') bg-blue-100 text-blue-800
                                                    @elseif ($job->status === 'completed') bg-green-100 text-green-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                                </span>
                                            </div>
                                            
                                            <div class="text-xs text-gray-600 space-y-1">
                                                <p>📅 Assigned: {{ $job->created_at->format('M d, Y g:i A') }}</p>
                                                @if ($job->completed_at)
                                                    <p>✅ Completed: {{ $job->completed_at->format('M d, Y g:i A') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Donor Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Donor Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Name</p>
                                    <p class="text-gray-900">{{ $wastePost->user->name }}</p>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Email</p>
                                    <p class="text-gray-900">{{ $wastePost->user->email }}</p>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Phone</p>
                                    <p class="text-gray-900">{{ $wastePost->user->phone ?? 'Not provided' }}</p>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Member Since</p>
                                    <p class="text-gray-900">{{ $wastePost->user->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Assignment -->
                    @if ($wastePost->latestJob && $wastePost->latestJob->collector)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assigned Collector</h3>
                                
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Name</p>
                                        <p class="text-gray-900">{{ $wastePost->latestJob->collector->name }}</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Phone</p>
                                        <p class="text-gray-900">{{ $wastePost->latestJob->collector->phone }}</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Location</p>
                                        <p class="text-gray-900">{{ $wastePost->latestJob->collector->location ?? 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Assigned On</p>
                                        <p class="text-gray-900">{{ $wastePost->latestJob->created_at->format('M d, Y') }}</p>
                                        <p class="text-xs text-gray-500">{{ $wastePost->latestJob->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                            
                            <div class="space-y-3">
                                @if ($wastePost->isOpen())
                                    <!-- Assign Collector -->
                                    <button onclick="openAssignModal()" 
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        Assign Collector
                                    </button>

                                    <!-- Cancel Post -->
                                    <form action="{{ route('admin.waste-posts.cancel', $wastePost) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                            onclick="return confirm('Cancel this waste post? This will notify the donor.')"
                                            class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                            Cancel Post
                                        </button>
                                    </form>
                                @endif

                                <!-- Delete Post -->
                                <form action="{{ route('admin.waste-posts.destroy', $wastePost) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                        onclick="return confirm('Delete this waste post? This action cannot be undone.')"
                                        class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        Delete Post
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Collector Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Collector</h3>
            
            <form action="{{ route('admin.waste-posts.assign', $wastePost) }}" method="POST" class="space-y-4">
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
                        <option value="">Choose a collector...</option>
                        @foreach ($activeCollectors as $collector)
                            <option value="{{ $collector->id }}">
                                {{ $collector->name }} - {{ $collector->location ?? 'No location' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($activeCollectors->isEmpty())
                        <p class="text-xs text-red-500 mt-1">No active collectors available</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">{{ $activeCollectors->count() }} collector(s) available</p>
                    @endif
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAssignModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" {{ $activeCollectors->isEmpty() ? 'disabled' : '' }}
                        class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Assign Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal() {
            const modal = document.getElementById('assignModal');
            modal.classList.remove('hidden');
        }

        function closeAssignModal() {
            const modal = document.getElementById('assignModal');
            modal.classList.add('hidden');
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
