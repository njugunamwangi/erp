    <div class="flex grid grid-cols-12 gap-2 border-b-[2px] cursor-pointer items-center rounded-[5px] py-[10px] px-1 hover:bg-gray-1 dark:hover:bg-gray-300">
        <div class="col-span-2 relative mr-[14px] h-11 w-full max-w-[44px] rounded-full shrink-0" >
            <img
                src="https://cdn.tailgrids.com/2.0/image/dashboard/images/chat-list/image-01.png"
                alt="profile"
                class="h-full w-full object-cover object-center "
                />
            <span
                class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-2 border-[#F8FAFC] dark:border-dark-2 bg-green-600"
                ></span>
        </div>

        <div class="w-full col-span-9">
            <div class="mb-1 flex justify-between">
                <h5 class="text-sm font-medium ">Danish Hebo</h5>
                <span class="text-xs text-body-color dark:text-dark-6"> Dec, 8 </span>
            </div>
            <div class="flex justify-between">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="h-5 w-5 mr-6 bi bi-check2-all" viewBox="0 0 16 16">
                    <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"/>
                    <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708"/>
                </svg>
                <p class="text-sm -ml-20 grow truncate max-w-[120px]">
                    Hello devid, how are you today?
                </p>
                <span
                    class="flex h-4 w-full max-w-[16px] items-center justify-center rounded-full bg-indigo-600 text-[10px] font-medium leading-none text-white"
                    >
                5
                </span>
            </div>
        </div>

        <div class="relative col-span-1 ">
            <button
                @click="openDropDown = !openDropDown"
                class=""
                >
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="h-6 w-6 bi bi-three-dots-vertical" viewBox="0 0 16 16">
                    <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                </svg>
            </button>
            <div
                x-show="openDropDown"
                @click.outside="openDropDown = false"
                class="absolute right-0 top-full z-40 w-[200px] space-y-1 rounded bg-white border dark:bg-dark p-2 shadow-card"
                >
                <button
                    class="w-full rounded py-2 px-3 text-left text-sm text-body-color dark:text-dark-6 hover:bg-gray-2 dark:hover:bg-dark-2"
                    >
                Archive
                </button>
                <button
                    class="w-full rounded py-2 px-3 text-left text-sm text-body-color dark:text-dark-6 hover:bg-gray-2 dark:hover:bg-dark-2"
                    >
                Delete
                </button>
            </div>
        </div>
    </div>