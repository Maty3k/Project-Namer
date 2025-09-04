<div class="{{ $collapsed ? 'w-16' : 'w-64' }} transition-all duration-300 ease-in-out transform {{ $collapsed ? '-translate-x-2' : 'translate-x-0' }} bg-gray-50 dark:bg-slate-900 border-r border-gray-200 dark:border-slate-700 h-screen flex flex-col">
    <!-- Sidebar Header -->
    <div class="p-4 border-b border-gray-200 dark:border-slate-600">
        <div class="flex items-center justify-between">
            @if(!$collapsed)
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Projects</h2>
            @endif
            <flux:button 
                wire:click="toggleCollapse"
                variant="ghost"
                size="sm"
                class="flex-shrink-0"
            >
                @if($collapsed)
                    <x-app-icon name="expand" size="sm" />
                @else
                    <x-app-icon name="collapse" size="sm" />
                @endif
            </flux:button>
        </div>
        
        @if(!$collapsed && $this->projectCount > 0)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $this->projectCount }} projects</p>
        @endif
    </div>

    <!-- New Project Button -->
    <div class="p-4">
        <flux:button
            wire:click="createNewProject"
            variant="primary"
            class="w-full {{ $collapsed ? 'px-3' : '' }}"
        >
            @if($collapsed)
                <x-app-icon name="add" size="sm" />
            @else
                <div class="flex items-center gap-2">
                    <x-app-icon name="add" size="sm" />
                    New Project
                </div>
            @endif
        </flux:button>
    </div>

    <!-- Projects List -->
    <div class="flex-1 overflow-y-auto">
        @if($this->projects->isEmpty())
            <!-- Empty State -->
            @if(!$collapsed)
                <div class="p-4 text-center">
                    <div class="text-gray-400 dark:text-gray-500 mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">No projects yet</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Create your first project</p>
                </div>
            @endif
        @else
            <!-- Projects List -->
            <div class="space-y-1 p-2">
                @foreach($this->projects as $project)
                    <div
                        wire:click="selectProject('{{ $project->uuid }}')"
                        class="cursor-pointer rounded-lg transition-all duration-200 
                               {{ $collapsed ? 'p-2' : 'p-3' }}
                               {{ $this->isActiveProject($project) 
                                   ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 shadow-sm' 
                                   : 'hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-sm transform hover:scale-[1.02]' }}"
                        wire:key="project-{{ $project->uuid }}"
                        @if($collapsed)
                            title="{{ $project->name }}"
                        @endif
                    >
                        @if($collapsed)
                            <!-- Collapsed view - Show icon only -->
                            <div class="flex items-center justify-center">
                                @if($project->selectedName)
                                    <!-- Project with selected name - show checkmark icon -->
                                    <svg class="w-5 h-5 {{ $this->isActiveProject($project) ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <!-- Regular project icon -->
                                    <svg class="w-5 h-5 {{ $this->isActiveProject($project) ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                @endif
                            </div>
                        @else
                            <!-- Expanded view -->
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $this->truncateName($project->name, 22) }}
                                    </h3>
                                    
                                    @if($project->selectedName)
                                        <div class="flex items-center mt-1">
                                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                ✓ {{ $this->truncateName($project->selectedName->name, 18) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate">
                                            {{ $this->truncateName($project->description, 25) }}
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                            {{ $this->truncateName($project->description, 35) }}
                                        </p>
                                    @endif
                                    
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $project->updated_at->format('M j') }}
                                    </p>
                                </div>
                                
                                @if($this->isActiveProject($project))
                                    <div class="flex-shrink-0 ml-2">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
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
    @if(!$collapsed)
        <div class="p-4 border-t border-gray-200 dark:border-slate-600">
            <div class="text-xs text-gray-400 dark:text-gray-500 text-center">
                Project Workflow UI
            </div>
        </div>
    @endif
</div>