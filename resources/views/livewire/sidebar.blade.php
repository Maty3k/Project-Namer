<div class="{{ $collapsed ? 'w-16' : 'w-64' }} transition-all duration-500 ease-out transform {{ $collapsed ? 'translate-x-0' : 'translate-x-0' }} border-r h-screen flex flex-col themed-sidebar will-change-transform"
     style="background-color: var(--surface-color);
            border-color: var(--primary-color);
            color: var(--text-color)">
    <!-- Sidebar Header -->
    <div class="p-4 border-b transition-all duration-300 ease-out" style="border-color: var(--text-secondary-color, #6b7280)">
        <div class="flex items-center transition-all duration-300 ease-out {{ $collapsed ? 'justify-center' : 'justify-between' }}">
            <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-w-0 opacity-0' : 'max-w-full opacity-100' }}">
                @if(!$collapsed)
                    <div class="flex items-center w-full">
                        <h2 class="text-lg font-semibold whitespace-nowrap transition-opacity duration-300 ease-out" style="color: var(--text-color)">Projects</h2>
                        @if($this->projectCount > 1)
                            <div class="flex-1"></div>
                            <flux:button
                                wire:click="toggleBulkSelectionMode"
                                variant="{{ $bulkSelectionMode ? 'danger' : 'ghost' }}"
                                size="sm"
                                class="transition-all duration-200 ml-6 {{ $bulkSelectionMode ? 'bg-red-500 hover:bg-red-600 text-white shadow-lg scale-110' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                title="{{ $bulkSelectionMode ? 'Exit bulk selection' : 'Select multiple projects for deletion' }}"
                            >
                                @if($bulkSelectionMode)
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <!-- Trash can with multiple items -->
                                        <path d="M3 6v14a2 2 0 002 2h14a2 2 0 002-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" fill="none"/>
                                        <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2"/>
                                        <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2"/>
                                        <!-- Multiple selection indicators -->
                                        <circle cx="6" cy="9" r="1" fill="currentColor"/>
                                        <circle cx="18" cy="9" r="1" fill="currentColor"/>
                                        <circle cx="6" cy="12" r="1" fill="currentColor"/>
                                        <circle cx="18" cy="12" r="1" fill="currentColor"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300 hover:text-red-500 dark:hover:text-red-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <!-- Trash can outline -->
                                        <path d="M3 6v14a2 2 0 002 2h14a2 2 0 002-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2"/>
                                        <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2"/>
                                        <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2"/>
                                        <!-- Multiple selection indicators (subtle) -->
                                        <circle cx="6" cy="9" r="0.5" fill="currentColor" opacity="0.4"/>
                                        <circle cx="18" cy="9" r="0.5" fill="currentColor" opacity="0.4"/>
                                        <circle cx="6" cy="12" r="0.5" fill="currentColor" opacity="0.4"/>
                                        <circle cx="18" cy="12" r="0.5" fill="currentColor" opacity="0.4"/>
                                    </svg>
                                @endif
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>
            <flux:button
                wire:click="toggleCollapse"
                variant="ghost"
                size="sm"
                class="flex-shrink-0 transition-transform duration-300 ease-out hover:scale-110 active:scale-95"
            >
                <div class="transition-transform duration-300 ease-out {{ $collapsed ? 'rotate-0' : 'rotate-180' }}">
                    @if($collapsed)
                        <x-app-icon name="expand" size="md" class="{{ $userTheme ? 'theme-icon' : '' }} transition-colors duration-200" />
                    @else
                        <x-app-icon name="collapse" size="md" class="{{ $userTheme ? 'theme-icon' : '' }} transition-colors duration-200" />
                    @endif
                </div>
            </flux:button>
        </div>

        <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-h-0 opacity-0 mt-0' : ($this->projectCount > 0 ? 'max-h-20 opacity-100 mt-1' : 'max-h-0 opacity-0 mt-0') }}">
            @if(!$collapsed && $this->projectCount > 0)
                <div class="flex items-center justify-between">
                    <p class="text-sm {{ $userTheme ? 'theme-text-muted' : 'text-gray-500 dark:text-gray-400' }} transition-opacity duration-300 ease-out delay-150">
                        {{ $this->projectCount }} project{{ $this->projectCount > 1 ? 's' : '' }}
                        @if($bulkSelectionMode && $this->selectedProjectsCount > 0)
                            <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                                {{ $this->selectedProjectsCount }} selected
                            </span>
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- New Project Button / Bulk Actions -->
    <div class="p-4 transition-all duration-300 ease-out">
        @if($bulkSelectionMode && !$collapsed)
            <!-- Bulk Selection Controls -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm" style="color: var(--text-color);">
                    <span>{{ $this->selectedProjectsCount }} selected</span>
                    <div class="flex gap-2">
                        <flux:button
                            wire:click="selectAllProjects"
                            variant="ghost"
                            size="xs"
                            title="Select all"
                        >
                            All
                        </flux:button>
                        <flux:button
                            wire:click="deselectAllProjects"
                            variant="ghost"
                            size="xs"
                            title="Deselect all"
                        >
                            None
                        </flux:button>
                    </div>
                </div>

                @if($this->selectedProjectsCount > 0)
                    <flux:button
                        wire:click="confirmBulkDeleteProjects"
                        variant="danger"
                        size="sm"
                        class="w-full transition-all duration-200 hover:scale-105"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">Delete {{ $this->selectedProjectsCount }}</span>
                        </div>
                    </flux:button>
                @endif
            </div>
        @else
            <!-- Normal New Project Button -->
            <flux:button wire:click="createNewProject" variant="primary" class="w-full transition-all duration-300 ease-out {{ $collapsed ? 'px-3' : 'px-4' }} relative overflow-hidden">
                <div class="flex items-center justify-center transition-all duration-300 ease-out {{ $collapsed ? 'gap-0' : 'gap-2' }}">
                    <x-app-icon name="document-plus" size="{{ $collapsed ? 'lg' : 'md' }}" class="transition-all duration-200 ease-out flex-shrink-0" />
                    <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-w-0 opacity-0 ml-0' : 'max-w-full opacity-100 ml-2' }}">
                        @if(!$collapsed)
                            <span class="whitespace-nowrap transition-opacity duration-300 ease-out delay-100">New Project</span>
                        @endif
                    </div>
                </div>
            </flux:button>
        @endif
    </div>

    <!-- Projects List -->
    <div class="flex-1 overflow-y-auto transition-all duration-300 ease-out">
        @if($this->projects->isEmpty())
            <!-- Empty State -->
            <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-h-0 opacity-0' : 'max-h-full opacity-100' }}">
                @if(!$collapsed)
                    <div class="p-4 text-center transition-all duration-300 ease-out delay-200">
                        <div class="mb-2 transition-all duration-300 ease-out" style="color: var(--text-muted-color, #9ca3af);">
                            <svg class="w-12 h-12 mx-auto transition-transform duration-300 ease-out" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <p class="text-sm mb-1 transition-opacity duration-300 ease-out" style="color: var(--text-secondary-color, #6b7280);">No projects yet</p>
                        <p class="text-xs transition-opacity duration-300 ease-out delay-75" style="color: var(--text-muted-color, #6b7280);">Create your first project</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Projects List -->
            <div class="space-y-1 p-2 transition-all duration-300 ease-out">
                @foreach($this->projects as $project)
                    <div
                        @if($bulkSelectionMode && !$collapsed)
                            wire:click="toggleProjectSelection('{{ $project->uuid }}')"
                        @else
                            wire:click="selectProject('{{ $project->uuid }}')"
                        @endif
                        class="group cursor-pointer rounded-lg transition-all duration-300 ease-out {{ $collapsed ? 'p-2' : 'p-3' }} {{ $userTheme ? ($this->isActiveProject($project) ? 'theme-interactive' : 'theme-hover') : 'hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-sm transform hover:scale-[1.02]' }}"
                        @if($this->isActiveProject($project) && !$bulkSelectionMode)
                            @if($userTheme)
                                style="background: {{ $userTheme->primary_color }}15; border-left: 4px solid {{ $userTheme->primary_color }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                            @else
                                style="background: {{ ($userTheme?->primary_color ?? '#3B82F6') }}15; border-left: 4px solid {{ ($userTheme?->primary_color ?? '#3B82F6') }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                            @endif
                        @elseif($bulkSelectionMode && $this->isProjectSelected($project->uuid))
                            style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3B82F6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                        @endif
                        wire:key="project-{{ $project->uuid }}"
                        @if($collapsed)
                            title="{{ $project->name }}"
                        @endif
                    >
                        <!-- Dynamic content based on collapsed state -->
                        @if($collapsed)
                            <div class="flex items-center justify-center relative">
                                @if($project->selectedName)
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                @endif
                            </div>
                        @else
                            <div class="overflow-hidden transition-all duration-500 ease-out">
                                <div class="flex items-start {{ $bulkSelectionMode ? 'gap-3' : 'justify-between group-hover:pr-8' }} transition-all duration-200">
                                    @if($bulkSelectionMode)
                                        <div class="flex-shrink-0 mt-1">
                                            <flux:checkbox
                                                wire:model.live="selectedProjectsForBulkDelete"
                                                value="{{ $project->uuid }}"
                                                class="pointer-events-none"
                                            />
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 transition-all duration-300 ease-out delay-150">
                                        <h3 class="text-sm font-medium truncate transition-opacity duration-300 ease-out" style="color: var(--text-color);">
                                            {{ $this->truncateName($project->name, 22) }}
                                        </h3>

                                        @if($project->selectedName)
                                            <div class="flex items-center mt-1 transition-all duration-300 ease-out delay-200">
                                                <span class="text-xs font-medium transition-colors duration-200 ease-out" style="color: var(--success-color, #059669);">
                                                    ✓ {{ $this->truncateName($project->selectedName->name, 18) }}
                                                </span>
                                            </div>
                                            <p class="text-xs mt-1 truncate transition-opacity duration-300 ease-out delay-250" style="color: var(--text-muted-color, #6b7280);">
                                                {{ $this->truncateName($project->description, 25) }}
                                            </p>
                                        @else
                                            <p class="text-xs mt-1 truncate transition-opacity duration-300 ease-out delay-200" style="color: var(--text-muted-color, #6b7280);">
                                                {{ $this->truncateName($project->description, 35) }}
                                            </p>
                                        @endif

                                        <p class="text-xs mt-1 transition-opacity duration-300 ease-out delay-300" style="color: var(--text-muted-color, #6b7280);">
                                            {{ $project->updated_at->format('M j') }}
                                        </p>
                                    </div>

                                    @if(!$bulkSelectionMode)
                                        <div class="flex-shrink-0 ml-2 flex items-center transition-all duration-300 ease-out delay-100">
                                            @if($this->isActiveProject($project))
                                                <div class="w-2 h-2 rounded-full mr-2 transition-all duration-200 ease-out" style="background-color: var(--primary-color, #3B82F6);"></div>
                                            @endif

                                            <button
                                                wire:click.stop="confirmDeleteProject('{{ $project->uuid }}')"
                                                class="opacity-60 group-hover:opacity-100 w-9 h-9 rounded-lg shadow-sm border flex items-center justify-center relative z-10"
                                                style="color: var(--danger-color, #ef4444); border-color: var(--danger-color, #ef4444); background-color: transparent;"
                                                title="Delete project"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Sidebar Footer (if expanded) -->
    <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-h-0 opacity-0' : 'max-h-20 opacity-100' }}">
        @if(!$collapsed)
            <div class="p-4 border-t transition-all duration-300 ease-out" style="border-color: var(--text-secondary-color, #6b7280);">
                <div class="text-xs text-center transition-opacity duration-300 ease-out delay-200" style="color: var(--text-muted-color, #6b7280);">
                    Project Workflow UI
                </div>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
        <flux:modal wire:model="showDeleteConfirmation" class="min-w-96" :closable="false">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Project</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete this project? This action cannot be undone.
                    </p>

                    @if($projectToDelete)
                        @php
                            $project = $this->projects->firstWhere('uuid', $projectToDelete);
                        @endphp
                        @if($project)
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <p class="font-medium text-sm">{{ $project->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $project->description }}</p>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="cancelDeleteProject"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="deleteProject"
                        variant="danger"
                    >
                        Delete Project
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Bulk Delete Confirmation Modal -->
    @if($showBulkDeleteConfirmation)
        <flux:modal wire:model="showBulkDeleteConfirmation" class="min-w-96" :closable="false">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Multiple Projects</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete {{ $this->selectedProjectsCount }} project{{ $this->selectedProjectsCount > 1 ? 's' : '' }}? This action cannot be undone.
                    </p>

                    @if(!empty($selectedProjectsForBulkDelete))
                        @php
                            $selectedProjects = $this->projects->whereIn('uuid', $selectedProjectsForBulkDelete);
                        @endphp
                        <div class="mt-3 max-h-60 overflow-y-auto space-y-2">
                            @foreach($selectedProjects as $project)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p class="font-medium text-sm">{{ $project->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $project->description }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="cancelBulkDeleteProjects"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="bulkDeleteProjects"
                        variant="danger"
                    >
                        Delete {{ $this->selectedProjectsCount }} Project{{ $this->selectedProjectsCount > 1 ? 's' : '' }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>