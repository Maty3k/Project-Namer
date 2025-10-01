<div class="{{ $collapsed ? 'w-16' : 'w-64' }} transition-all duration-500 ease-out transform {{ $collapsed ? 'translate-x-0' : 'translate-x-0' }} border-r h-screen flex flex-col themed-sidebar will-change-transform"
     @php
         $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
     @endphp
     @if($userTheme)
         style="background-color: {{ $userTheme->is_dark_mode ? $userTheme->background_color : ($userTheme->surface_color ?? '#f8fafc') }};
                border-color: {{ $userTheme->primary_color }}50;
                color: {{ $userTheme->text_color }};"
     @else
         class="bg-gray-50 dark:bg-slate-900 border-gray-200 dark:border-slate-700"
     @endif>
    <!-- Sidebar Header -->
    <div class="p-4 border-b {{ $userTheme ? 'border-current border-opacity-20' : 'border-gray-200 dark:border-slate-600' }} transition-all duration-300 ease-out">
        <div class="flex items-center transition-all duration-300 ease-out {{ $collapsed ? 'justify-center' : 'justify-between' }}">
            <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-w-0 opacity-0' : 'max-w-full opacity-100' }}">
                @if(!$collapsed)
                    <h2 class="text-lg {{ $userTheme ? 'theme-text-primary' : 'text-gray-900 dark:text-white font-semibold' }} whitespace-nowrap transition-opacity duration-300 ease-out">Projects</h2>
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
                <p class="text-sm {{ $userTheme ? 'theme-text-muted' : 'text-gray-500 dark:text-gray-400' }} transition-opacity duration-300 ease-out delay-150">{{ $this->projectCount }} projects</p>
            @endif
        </div>
    </div>

    <!-- New Project Button -->
    <div class="p-4 transition-all duration-300 ease-out">
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
    </div>

    <!-- Bulk Delete Controls -->
    @if(!$this->projects->isEmpty() && !$collapsed)
        <div class="px-4 pb-4 border-b {{ $userTheme ? 'border-current border-opacity-20' : 'border-gray-200 dark:border-slate-600' }}">
            @if($bulkDeleteMode)
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <flux:button
                            wire:click="selectAllProjects"
                            variant="ghost"
                            size="sm"
                            class="flex-1 text-xs"
                        >
                            Select All
                        </flux:button>
                        <flux:button
                            wire:click="deselectAllProjects"
                            variant="ghost"
                            size="sm"
                            class="flex-1 text-xs"
                        >
                            Deselect All
                        </flux:button>
                    </div>
                    <div class="flex gap-2">
                        <flux:button
                            wire:click="confirmBulkDelete"
                            variant="danger"
                            size="sm"
                            class="flex-1"
                            :disabled="count($selectedProjects) === 0"
                        >
                            Delete ({{ count($selectedProjects) }})
                        </flux:button>
                        <flux:button
                            wire:click="toggleBulkDeleteMode"
                            variant="ghost"
                            size="sm"
                            class="flex-1"
                        >
                            Cancel
                        </flux:button>
                    </div>
                </div>
            @else
                <flux:button
                    wire:click="toggleBulkDeleteMode"
                    variant="ghost"
                    size="sm"
                    class="w-full"
                >
                    <div class="flex items-center gap-2">
                        <x-app-icon name="trash" size="sm" />
                        <span>Bulk Delete</span>
                    </div>
                </flux:button>
            @endif
        </div>
    @endif

    <!-- Projects List -->
    <div class="flex-1 overflow-y-auto transition-all duration-300 ease-out">
        @if($this->projects->isEmpty())
            <!-- Empty State -->
            <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-h-0 opacity-0' : 'max-h-full opacity-100' }}">
                @if(!$collapsed)
                    <div class="p-4 text-center transition-all duration-300 ease-out delay-200">
                        <div class="text-gray-400 dark:text-gray-500 mb-2 transition-all duration-300 ease-out">
                            <svg class="w-12 h-12 mx-auto transition-transform duration-300 ease-out" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <p class="text-sm {{ $userTheme ? 'theme-text-secondary' : 'text-gray-500 dark:text-gray-400' }} mb-1 transition-opacity duration-300 ease-out">No projects yet</p>
                        <p class="text-xs {{ $userTheme ? 'theme-text-muted' : 'text-gray-400 dark:text-gray-500' }} transition-opacity duration-300 ease-out delay-75">Create your first project</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Projects List -->
            <div class="space-y-1 p-2 transition-all duration-300 ease-out">
                @foreach($this->projects as $project)
                    <div
                        wire:click="{{ $bulkDeleteMode ? '' : 'selectProject(\'' . $project->uuid . '\')' }}"
                        class="group cursor-pointer rounded-lg transition-all duration-300 ease-out {{ $collapsed ? 'p-2' : 'p-3' }} {{ $userTheme ? ($this->isActiveProject($project) ? 'theme-interactive' : 'theme-hover') : 'hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-sm transform hover:scale-[1.02]' }}"
                        @if($this->isActiveProject($project))
                            @if($userTheme)
                                style="background: {{ $userTheme->primary_color }}15; border-left: 4px solid {{ $userTheme->primary_color }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                            @else
                                style="background: {{ ($userTheme?->primary_color ?? '#3B82F6') }}15; border-left: 4px solid {{ ($userTheme?->primary_color ?? '#3B82F6') }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                            @endif
                        @endif
                        wire:key="project-{{ $project->uuid }}"
                        @if($collapsed)
                            title="{{ $project->name }}"
                        @endif
                    >
                        @if($collapsed)
                            <!-- Collapsed view - Show icon only -->
                            <div class="flex items-center justify-center relative">
                                @if($project->selectedName)
                                    <!-- Project with selected name - show checkmark icon -->
                                    <svg class="w-6 h-6 {{ $userTheme ? ($this->isActiveProject($project) ? 'theme-icon' : 'text-green-600 opacity-90') : 'text-green-600 dark:text-green-400' }}" 
                                         @if(!$userTheme && $this->isActiveProject($project)) style="color: {{ ($userTheme?->primary_color ?? '#3B82F6') }};" @endif
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <!-- Regular project icon -->
                                    <svg class="w-6 h-6 {{ $userTheme ? 'theme-icon' : ($this->isActiveProject($project) ? '' : 'text-gray-600 dark:text-gray-400') }}" 
                                         @if(!$userTheme && $this->isActiveProject($project)) style="color: {{ ($userTheme?->primary_color ?? '#3B82F6') }};" @endif
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                @endif
                                
                            </div>
                        @else
                            <!-- Expanded view -->
                            <div class="overflow-hidden transition-all duration-500 ease-out {{ $collapsed ? 'max-w-0 opacity-0' : 'max-w-full opacity-100' }}">
                                @if(!$collapsed)
                                    <div class="flex items-start gap-3 group-hover:pr-8 transition-all duration-200">
                                        @if($bulkDeleteMode)
                                            <div class="flex-shrink-0 pt-1">
                                                <flux:checkbox
                                                    wire:click.stop="toggleProjectSelection('{{ $project->uuid }}')"
                                                    :checked="in_array($project->uuid, $selectedProjects)"
                                                />
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0 transition-all duration-300 ease-out delay-150">
                                            <h3 class="text-sm {{ $userTheme ? 'theme-text-primary' : 'text-gray-900 dark:text-white font-medium' }} truncate transition-opacity duration-300 ease-out">
                                                {{ $this->truncateName($project->name, 22) }}
                                            </h3>
                                    
                                            @if($project->selectedName)
                                                <div class="flex items-center mt-1 transition-all duration-300 ease-out delay-200">
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium transition-colors duration-200 ease-out">
                                                        ✓ {{ $this->truncateName($project->selectedName->name, 18) }}
                                                    </span>
                                                </div>
                                                <p class="text-xs {{ $userTheme ? 'theme-text-muted' : 'text-gray-400 dark:text-gray-500' }} mt-1 truncate transition-opacity duration-300 ease-out delay-250">
                                                    {{ $this->truncateName($project->description, 25) }}
                                                </p>
                                            @else
                                                <p class="text-xs {{ $userTheme ? 'theme-text-muted' : 'text-gray-500 dark:text-gray-400' }} mt-1 truncate transition-opacity duration-300 ease-out delay-200">
                                                    {{ $this->truncateName($project->description, 35) }}
                                                </p>
                                            @endif

                                            <p class="text-xs {{ $userTheme ? 'theme-text-muted' : 'text-gray-400 dark:text-gray-500' }} mt-1 transition-opacity duration-300 ease-out delay-300">
                                                {{ $project->updated_at->format('M j') }}
                                            </p>
                                        </div>

                                        @if(!$bulkDeleteMode)
                                            <div class="flex-shrink-0 ml-2 flex items-center transition-all duration-300 ease-out delay-100">
                                                @if($this->isActiveProject($project))
                                                    <div class="w-2 h-2 {{ $userTheme ? 'bg-current opacity-70' : '' }} rounded-full mr-2 transition-all duration-200 ease-out"
                                                         @if(!$userTheme) style="background-color: {{ ($userTheme?->primary_color ?? '#3B82F6') }};" @endif></div>
                                                @endif

                                                <!-- Delete Button (hidden by default, shown on hover) -->
                                                <flux:button
                                                    wire:click.stop="confirmDeleteProject('{{ $project->uuid }}')"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="opacity-0 group-hover:opacity-100 w-9 h-9 rounded-lg shadow-sm transition-all duration-200 ease-out {{ $userTheme ? 'theme-hover-delete' : 'hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-red-500 border border-red-200' }} hover:scale-105 active:scale-95"
                                                    style="display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important;"
                                                    title="Delete project"
                                                >
                                                    <svg class="w-5 h-5 transition-transform duration-200 ease-out" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </flux:button>
                                            </div>
                                        @endif
                                    </div>
                                @endif
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
            <div class="p-4 border-t {{ $userTheme ? 'border-current border-opacity-20' : 'border-gray-200 dark:border-slate-600' }} transition-all duration-300 ease-out">
                <div class="text-xs {{ $userTheme ? 'theme-text-muted' : 'text-gray-400 dark:text-gray-500' }} text-center transition-opacity duration-300 ease-out delay-200">
                    Project Workflow UI
                </div>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
        <flux:modal wire:model="showDeleteConfirmation" class="min-w-96">
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
        <flux:modal wire:model="showBulkDeleteConfirmation">
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Delete Multiple Projects?
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete {{ count($selectedProjects) }} {{ count($selectedProjects) === 1 ? 'project' : 'projects' }}?
                        This action cannot be undone.
                    </p>
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="cancelBulkDelete"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="bulkDeleteProjects"
                        variant="danger"
                    >
                        Delete {{ count($selectedProjects) }} {{ count($selectedProjects) === 1 ? 'Project' : 'Projects' }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>