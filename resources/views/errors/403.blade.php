    <!-- ====== Error 403 Section Start -->
    <x-guest-layout>
        <section class="relative z-10 flex min-h-screen items-center overflow-hidden bg-white dark:bg-dark py-20 lg:py-[120px]">
            <div class="container mx-auto">
                <div class="flex flex-wrap -mx-4">
                    <div class="w-full px-4 lg:w-1/2">
                        <div class="mb-12 w-full max-w-[470px] lg:mb-0">
                        <h2
                            class="mb-6 text-[40px] font-bold uppercase text-primary dark:text-gray-600 sm:text-[54px]"
                            >
                            403 Forbidden
                        </h2>
                        <h3
                            class="mb-3 text-2xl font-semibold text-dark dark:text-gray-500 sm:text-3xl"
                            >
                            Hold it right there!!
                        </h3>
                        <p class="mb-12 text-lg text-body-color dark:text-gray-500">
                            You are not authorized to view this page,
                        </p>
                        <a
                            href="/"
                            wire:navigate
                            class="inline-flex px-8 py-3 text-base font-medium text-white transition border border-transparent dark:bg-blue-400 dark:text-white rounded bg-primary hover:bg-opacity-90"
                            >
                        Back to Homepage
                        </a>
                        </div>
                    </div>
                    <div class="w-full px-4 lg:w-1/2">
                        <div class="mx-auto text-center">
                        <img
                            src="https://cdn.tailgrids.com/2.0/image/application/images/404/image-08.svg"
                            alt="404 image"
                            class="max-w-full mx-auto"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="absolute top-0 left-0 block w-full h-full -z-10 bg-gray dark:bg-dark-2 lg:w-1/2"></div>
        </section>
    </x-guest-layout>
    <!-- ====== Error 403 Section End -->