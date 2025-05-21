<div class="max-w-full space-y-8">
    <!-- Información de la derivación -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6">
        <div class="mb-5">
            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-base font-bold uppercase tracking-wide">
                    Información de la derivación
                </h3>
            </div>
        </div>
        
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center py-2 px-1 border-b border-gray-100 dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">Origen:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $originDepartment }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 px-1 border-b border-gray-100 dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">Derivado por:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $derivatedBy }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 px-1 border-b border-gray-100 dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">Destino:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $destinationDepartment }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 px-1 border-b border-gray-100 dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">Fecha:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $created_at }}</span>
            </div>
        </div>
    </div>

    <!-- Historial de seguimiento -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6">
        <div class="mb-5">
            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-base font-bold uppercase tracking-wide">
                    Historial de seguimiento
                </h3>
            </div>
        </div>
        
        @if($details->isEmpty())
            <div class="text-center p-6 bg-gray-50/30 dark:bg-gray-800 rounded-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    Sin registros de seguimiento
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($details as $detail)
                    <div class="group relative bg-gray-50/50 dark:bg-gray-800 p-4 rounded-lg border border-gray-100 dark:border-gray-700 transition-all hover:border-gray-200 dark:hover:border-gray-600">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="relative flex-none">
                                    <span class="absolute inset-0 animate-ping opacity-40 rounded-full {{ $detail->status === 'Enviado' ? 'bg-amber-400' : ($detail->status === 'Recibido' ? 'bg-emerald-400' : 'bg-rose-400') }}"></span>
                                    <span class="relative block w-3 h-3 rounded-full {{ $detail->status === 'Enviado' ? 'bg-amber-400' : ($detail->status === 'Recibido' ? 'bg-emerald-400' : 'bg-rose-400') }}"></span>
                                </div>
                                
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">
                                            {{ $detail->user->name }}
                                        </h4>
                                    </div>
                                    <time class="block sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $detail->created_at->format('d/m/Y H:i') }}
                                    </time>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 sm:pl-3">
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $detail->status === 'Enviado' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20' : ($detail->status === 'Recibido' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/20') }}">
                                    <!-- Iconos de estado -->
                                    @if($detail->status === 'Enviado')
                                    <svg class="inline w-4 h-4 mr-1 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                    </svg>
                                    @elseif($detail->status === 'Recibido')
                                    <svg class="inline w-4 h-4 mr-1 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    @else
                                    <svg class="inline w-4 h-4 mr-1 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    @endif
                                    {{ $detail->status }}
                                </span>
                                <time class="hidden sm:block text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $detail->created_at->format('d/m/Y H:i') }}
                                </time>
                            </div>
                        </div>
                        
                        @if($detail->comments)
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-300 leading-snug">
                                    {{ $detail->comments }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>