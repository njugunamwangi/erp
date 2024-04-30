    <div>
        <ul class="space-y-3 ">
            @foreach($list as $chat)
                <li class="flex cursor-pointer items-center rounded-[5px] gap-x-2 py-2 px-4">
                    <!-- Avatar -->
                    <a class="shrink-0 relative mr-[14px] h-11 w-11 max-w-[44px] rounded-full" >
                        <img
                            src="{{ $chat->getFilamentAvatarUrl() }}"
                            alt="{{ $chat->name }}"
                            class="h-full w-full rounded-full object-cover object-center"
                            />
                        <span
                            class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-2 border-[#F8FAFC] dark:border-indigo-700 bg-green-600 dark:bg-green-600" >
                            </span>
                    </a>

                    <!-- Chat -->
                    <aside class="grid grid-cols-12 w-full">
                        <div class="col-span-11 border-b border-gray-200 dark:border-white relative overflow-hidden truncate leading-5 w-full flex-nowrap p-1 ">
                            <!-- Name & Date -->
                            <div class="mb-1 flex justify-between items-center">
                                <h5 class="text-sm font-medium text-dark dark:text-white">{{ $chat->name }}</h5>
                                <span class="text-xs text-body-color dark:text-dark-6"> Dec, 8 </span>
                            </div>

                            <!-- Ticks and text snippet -->
                            <div class="flex gap-x-2 items-center mb-2">
                                <span >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
                                        <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"/>
                                        <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708"/>
                                    </svg>
                                </span>

                                <!-- <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
                                    </svg>
                                </span> -->

                                <p class="grow truncate text-sm text-dark dark:text-white">
                                    Lorem ipsum dolor
                                </p>
                                <span class="font-bold p-px px-2 text-xs shrink-0 rounded-full bg-blue-500 text-dark dark:text-white" >5</span>
                            </div>
                        </div>
                    </aside>
                </li>
            @endforeach
        </ul>
    </div>