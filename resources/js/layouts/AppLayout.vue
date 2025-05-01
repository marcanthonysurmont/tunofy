<template>
  <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-white">
      <body class="h-full">
      ```
    -->
  <div>
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
        <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0"
          enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
          leave-to="opacity-0">
          <div class="fixed inset-0 bg-navbar-background/80" />
        </TransitionChild>

        <div class="fixed inset-0 flex">
          <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full" enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
            leave-to="-translate-x-full">
            <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
              <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0"
                enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                  <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="size-6 text-white" aria-hidden="true" />
                  </button>
                </div>
              </TransitionChild>
              <!-- Sidebar component, swap this element with another sidebar if you like -->
              <div
                class="flex grow flex-col gap-y-5 overflow-y-auto bg-navbar-background px-6 pb-2 ring-1 ring-white/10">
                <div class="flex h-16 shrink-0 items-center">
                  <img class="h-8 w-auto" src="/images/logos/tunofy-logo-white.png" alt="Tunofy" />
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in navigation" :key="item.name">
                          <a :href="item.href" :class="[
                            item.current
                              ? 'bg-zinc-700 text-white'
                              : 'text-gray-400 hover:bg-zinc-800 hover:text-white',
                            'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold',
                          ]">
                            <component :is="item.icon" class="size-6 shrink-0" aria-hidden="true" />
                            {{ item.name }}
                          </a>
                        </li>
                      </ul>
                    </li>
                    <li>
                    <li class="min-h-[200px]">
                      <div class="flex flex-row justify-between items-center">
                        <h1 class="text-2xl">Your Jams</h1>
                        <PlusIcon class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg" />
                      </div>
                      <ul role="list" class="-mx-2 space-y-1">
                      </ul>
                    </li>
                    <li class="min-h-[200px]">
                      <div class="flex flex-row justify-between items-center">
                        <h1 class="text-2xl">Joined Jams</h1>
                        <PlusIcon class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg" />
                      </div>
                    </li>
                    </li>
                  </ul>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for DESKTOP -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
      <div
        class="flex grow flex-col gap-y-5 overflow-y-auto bg-navbar-background border-r-2 border-regular-stroke px-6">
        <div class="flex h-16 shrink-0 items-center">
          <img class="h-8 w-auto" src="/images/logos/tunofy-logo-white.png" alt="Tunofy" />
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li class="min-h-[200px]">
              <div class="flex flex-row justify-between items-center">
                <h1 class="text-2xl">Your Jams</h1>
                <PlusIcon class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg" />
              </div>
              <ul role="list" class="-mx-2 space-y-1">
              </ul>
            </li>
            <li class="min-h-[200px]">
              <div class="flex flex-row justify-between items-center">
                <h1 class="text-2xl">Joined Jams</h1>
                <PlusIcon class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg" />
              </div>
            </li>
            <li
              class="-mx-6 mt-auto flex flex-row items-center justify-between border-t border-regular-stroke px-6 py-3">
              <a href="#" class="flex items-center gap-x-2 text-sm/6 font-bold text-white">
                <img class="size-10 border-regular-stroke border-2 rounded-full bg-zinc-700"
                  src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                  alt="User avatar of logged in user" />
                <span class="sr-only">Your profile</span>
                <span aria-hidden="true">Gilles Serrien</span>
              </a>
              <div class="flex items-center gap-x-2">
                <Link href="settings" method="post">
                <Cog8ToothIcon class="size-6 text-white stroke-2 cursor-pointer" />
                </Link>
                <Link href="logout" method="post">
                <ArrowLeftEndOnRectangleIcon class="size-6 stroke-2 text-white font-bold cursor-pointer" />
                </Link>
              </div>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <div
      class="sticky top-0 z-40 flex items-center gap-x-6 bg-navbar-background px-4 py-4 shadow-xs sm:px-6 lg:hidden border-b-2 border-regular-stroke">
      <button type="button" class="-m-2.5 p-2.5 text-gray-400 lg:hidden" @click="sidebarOpen = true">
        <span class="sr-only">Open sidebar</span>
        <Bars3Icon class="size-6" aria-hidden="true" />
      </button>
      <div class="flex-1 text-sm/6 font-semibold text-white">
        Dashboard
      </div>
      <a href="#">
        <span class="sr-only">Your profile</span>
        <img class="size-8 rounded-full bg-zinc-700"
          src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
          alt="" />
      </a>
    </div>

    <main class="py-10 lg:pl-72">
      <div class="px-4 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from "vue";
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import {
  ArrowLeftEndOnRectangleIcon,
  Bars3Icon,
  Cog8ToothIcon,
  PlusIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

import { Link } from "@inertiajs/vue3";

const sidebarOpen = ref(false);
</script>
