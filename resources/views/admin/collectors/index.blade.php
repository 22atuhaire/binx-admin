<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Active Collectors') }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.collectors.pending') }}" class="text-sm text-blue-600 hover:text-blue-900">
                    Pending Collectors
                </a>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Back to Dashboard
                </a>
            </div>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Active Collectors ({{ $collectors->total() }})
                        </h3>
                    </div>

                    @if ($collectors->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg">No active collectors</p>
                            <p class="text-gray-400 text-sm mt-2">Approve pending collectors to add them to the system</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Jobs Completed</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($collectors as $collector)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $collector->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $collector->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $collector->phone ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $collector->address ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900">{{ $collector->completed_jobs ?? 0 }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-1">
                                                    @if ($collector->rating > 0)
                                                        <span class="text-sm font-medium text-gray-900">{{ number_format($collector->rating, 1) }}</span>
                                                        <span class="text-yellow-400">★</span>
                                                    @else
                                                        <span class="text-sm text-gray-400">No ratings</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($collector->isSuspended())
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Suspended
                                                    </span>
                                                @else
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $collector->created_at->format('M d, Y') }}
                                                <div class="text-xs text-gray-400">{{ $collector->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex flex-col gap-2">
                                                    <!-- View Profile -->
                                                    <button onclick="openProfileModal({{ $collector->id }}, '{{ addslashes($collector->name) }}', '{{ addslashes($collector->address) }}', '{{ $collector->isSuspended() ? 'Suspended' : 'Active' }}', '{{ addslashes($collector->suspension_reason ?? '') }}')"
                                                        class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                                                        View Profile
                                                    </button>

                                                    <!-- View Job History -->
                                                    <a href="{{ route('admin.collectors.job-history', $collector) }}"
                                                        class="text-purple-600 hover:text-purple-900 text-xs font-medium">
                                                        Job History
                                                    </a>

                                                    @if ($collector->isSuspended())
                                                        <!-- Reactivate Button -->
                                                        <form action="{{ route('admin.collectors.reactivate', $collector) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                onclick="return confirm('Reactivate {{ addslashes($collector->name) }}?')"
                                                                class="text-green-600 hover:text-green-900 text-xs font-medium">
                                                                Reactivate
                                                            </button>
                                                        </form>
                                                    @else
                                                        <!-- Suspend Button -->
                                                        <button onclick="openSuspendModal({{ $collector->id }}, '{{ addslashes($collector->name) }}')"
                                                            class="text-red-600 hover:text-red-900 text-xs font-medium">
                                                            Suspend
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $collectors->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4" id="profileTitle">Collector Profile</h3>
            
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Location</p>
                    <p class="text-sm text-gray-900" id="profileLocation">-</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                    <p class="text-sm text-gray-900" id="profileStatus">Active</p>
                </div>

                <div id="suspensionReasonDiv" class="hidden">
                    <p class="text-xs font-medium text-gray-500 uppercase">Suspension Reason</p>
                    <p class="text-sm text-gray-900" id="profileSuspensionReason">-</p>
                </div>

                <div class="flex justify-end">
                    <button type="button" onclick="closeProfileModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-900 rounded-md text-sm font-medium hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspension Modal -->
    <div id="suspendModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Suspend Collector</h3>
            
            <form id="suspendForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Suspension
                    </label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Provide a detailed reason for suspending this collector (10-500 characters)..."
                        minlength="10"
                        maxlength="500"
                        required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeSuspendModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                        Suspend Collector
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openProfileModal(collectorId, collectorName, location, status, suspensionReason) {
            const modal = document.getElementById('profileModal');
            
            document.getElementById('profileTitle').textContent = `${collectorName}'s Profile`;
            document.getElementById('profileLocation').textContent = location || 'N/A';
            document.getElementById('profileStatus').textContent = status;
            
            const reasonDiv = document.getElementById('suspensionReasonDiv');
            if (status === 'Suspended' && suspensionReason) {
                document.getElementById('profileSuspensionReason').textContent = suspensionReason;
                reasonDiv.classList.remove('hidden');
            } else {
                reasonDiv.classList.add('hidden');
            }
            
            modal.classList.remove('hidden');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }

        function openSuspendModal(collectorId, collectorName) {
            const modal = document.getElementById('suspendModal');
            const form = document.getElementById('suspendForm');
            
            form.action = `/admin/collectors/${collectorId}/suspend`;
            modal.querySelector('h3').textContent = `Suspend ${collectorName}`;
            
            form.reset();
            document.getElementById('reason').focus();
            
            modal.classList.remove('hidden');
        }

        function closeSuspendModal() {
            const modal = document.getElementById('suspendModal');
            modal.classList.add('hidden');
            document.getElementById('suspendForm').reset();
        }

        // Close modals when clicking outside
        ['profileModal', 'suspendModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
                closeSuspendModal();
            }
        });
    </script>
</x-app-layout>
