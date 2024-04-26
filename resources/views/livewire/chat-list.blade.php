<div>
    @foreach($list as $chat)
        <div class="flex cursor-pointer items-center  py-[10px] px-4 hover:bg-gray-700 dark:hover:bg-teal-600 transition ease-in-out delay-150 hover:-translate-y-1 hover:scale-110 duration-300">

            <div class="shrink-0 relative mr-[14px] h-11 w-full max-w-[44px] rounded-full">
                <img
                    src="https://cdn.tailgrids.com/2.0/image/dashboard/images/chat-list/image-01.png"
                    alt="profile"
                    class="h-full w-full object-cover object-center"
                    />
                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-2 border-[#F8FAFC] dark:border-dark-2 bg-green-600 dark:bg-green-600"></span>
            </div>

            <div class="w-full border-b-[2px]">

                <div class="mb-1 flex justify-between">
                    <h5 class="text-sm font-medium text-dark dark:text-white">{{ $chat->name }}</h5>
                    <span class="text-xs text-body-color dark:text-dark-6"> Dec, 8 </span>
                </div>

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

                    <p class="grow truncate w-full text-sm text-dark dark:text-white">
                        Lorem, ipsum dolor sit amet
                    </p>

                    <span class="flex h-4 w-full max-w-[16px] items-center justify-center rounded-full bg-emerald-900 dark:bg-emerald-900 text-[10px] font-medium leading-none text-white" >
                    5
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>