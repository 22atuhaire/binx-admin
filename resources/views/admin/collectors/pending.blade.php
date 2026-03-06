<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pending Collectors') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Collectors Awaiting Approval ({{ $pendingCollectors->total() }})
                        </h3>
                    </div>

                    @if ($pendingCollectors->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg">No pending collectors</p>
                            <p class="text-gray-400 text-sm mt-2">All collectors have been reviewed</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">ID Photo</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Applied On</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pendingCollectors as $collector)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $collector->name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $collector->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $collector->phone ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500">{{ $collector->address ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($collector->id_document)
                                                    <a href="{{ Storage::url($collector->id_document) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-900">
                                                        View Document
                                                    </a>
                                                @else
                                                    <span class="text-sm text-gray-400">Not provided</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $collector->created_at->format('M d, Y') }}
                                                <div class="text-xs text-gray-400">{{ $collector->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <!-- Approve Button -->
                                                <form action="{{ route('admin.collectors.approve', $collector) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                        onclick="return confirm('Approve {{ addslashes($collector->name) }} as a collector?')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                        Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Button with Modal -->
                                                <button onclick="openRejectModal({{ $collector->id }}, '{{ addslashes($collector->name) }}')"
                                                    class="inline-flex items-center px-3 py-1.5 ml-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $pendingCollectors->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Collector Application</h3>
            
            <form id="rejectForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Rejection
                    </label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Provide a detailed reason for rejecting this application (10-500 characters)..."
                        minlength="10"
                        maxlength="500"
                        required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                        Reject Collector
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(collectorId, collectorName) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            
            // Update form action to the correct route
            form.action = `/admin/collectors/${collectorId}/reject`;
            
            // Update modal title with collector name
            const title = modal.querySelector('h3');
            title.textContent = `Reject ${collectorName}'s Application`;
            
            // Reset form and textarea
            form.reset();
            document.getElementById('reason').focus();
            
            // Show modal
            modal.classList.remove('hidden');
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
            document.getElementById('rejectForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });
    </script>
</x-app-layout>

