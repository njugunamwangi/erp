<x-chats-layout >

    <x-filament::section class="w-1/4 h-[1230px] ">
        <div class="mx-auto px-4 md:container">
            <div class="shadow-1 dark:shadow-box-dark mx-auto max-w-[450px] rounded-lg bg-white dark:bg-stone-900 py-8 px-2">
                <div class="mb-8 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-dark dark:text-white">Messages</h3>
                    <div class="relative">
                    <button
                        @click="openDropDown = !openDropDown"
                        class="text-dark dark:text-white"
                        >
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            class="fill-current"
                            >
                            <path
                                d="M5 14C6.10457 14 7 13.1046 7 12C7 10.8954 6.10457 10 5 10C3.89543 10 3 10.8954 3 12C3 13.1046 3.89543 14 5 14Z"
                                />
                            <path
                                d="M12 14C13.1046 14 14 13.1046 14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14Z"
                                />
                            <path
                                d="M19 14C20.1046 14 21 13.1046 21 12C21 10.8954 20.1046 10 19 10C17.8954 10 17 10.8954 17 12C17 13.1046 17.8954 14 19 14Z"
                                />
                        </svg>
                    </button>
                    <div
                        x-show="openDropDown"
                        @click.outside="openDropDown = false"
                        class="absolute right-0 top-full z-40 w-[200px] space-y-1 rounded bg-white dark:bg-dark p-2 shadow-card"
                        >
                        <button
                            class="w-full rounded py-2 px-3 text-left text-sm text-body-color dark:text-dark-6 hover:bg-gray-2 dark:hover:bg-dark-2"
                            >
                        All Conversations
                        </button>
                        <button
                            class="w-full rounded py-2 px-3 text-left text-sm text-body-color dark:text-dark-6 hover:bg-gray-2 dark:hover:bg-dark-2"
                            >
                        Mark as Read
                        </button>
                    </div>
                    </div>
                </div>
                <form class="relative mb-7">
                    <input
                    type="text"
                    class="w-full bg-transparent rounded-full border border-stroke dark:border-dark-3 py-[10px] pl-5 pr-10 text-base text-body-color dark:text-dark-6 outline-none focus:border-primary"
                    placeholder="Search.."
                    />
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-body-color dark:text-dark-6 hover:text-primary">
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        class="fill-current stroke-current"
                        >
                        <path
                            d="M15.0348 14.3131L15.0348 14.3132L15.0377 14.3154C15.0472 14.323 15.0514 14.3294 15.0536 14.3334C15.0559 14.3378 15.0576 14.343 15.0581 14.3496C15.0592 14.3621 15.0563 14.3854 15.0346 14.4127C15.0307 14.4175 15.0275 14.4196 15.0249 14.4208C15.0224 14.422 15.0152 14.425 15 14.425C15.0038 14.425 14.9998 14.4256 14.9885 14.4215C14.9785 14.4178 14.9667 14.4118 14.9549 14.4036L10.7893 11.0362L10.4555 10.7663L10.1383 11.0554C9.10154 12 7.79538 12.525 6.4 12.525C4.933 12.525 3.56006 11.953 2.52855 10.9215C0.398817 8.79172 0.398817 5.3083 2.52855 3.17857C3.56006 2.14706 4.933 1.57501 6.4 1.57501C7.867 1.57501 9.23994 2.14706 10.2714 3.17857L10.2714 3.17857L10.2735 3.18065C12.2161 5.10036 12.3805 8.14757 10.8214 10.2799L10.5409 10.6635L10.9098 10.9631L15.0348 14.3131ZM2.62145 10.8286C3.63934 11.8465 4.96616 12.4 6.4 12.4C7.82815 12.4 9.1825 11.8504 10.1798 10.8273C12.2759 8.75493 12.2713 5.36421 10.1786 3.27146C9.16066 2.25356 7.83384 1.70001 6.4 1.70001C4.96672 1.70001 3.64038 2.25313 2.62264 3.27027C0.524101 5.34244 0.527875 8.73499 2.62145 10.8286Z"
                            />
                    </svg>
                    </button>
                </form>
                <div class="space-y-4">
                    <livewire:chat-list />
                </div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section class="w-3/4 h-[1230px] text-center justify-center items-center flex flex-col">

        <h4 class="font-medium text-lg"> Choose a conversation to start chatting </h4>

    </x-filament::section>

</x-chats-layout>
