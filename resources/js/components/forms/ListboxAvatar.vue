<template>
    <Listbox as="div" v-model="selected">
        <ListboxLabel class="block text-sm/6 font-medium text-zinc-300">{{
            label
        }}</ListboxLabel>
        <div class="relative mt-2">
            <ListboxButton
                class="grid w-full cursor-default grid-cols-1 rounded-md bg-inputfield-background py-1.5 pr-2 pl-3 text-left text-zinc-100 outline-1 -outline-offset-1 outline-inputfield-stroke focus:outline-2 focus:-outline-offset-2 focus:outline-zinc-700 sm:text-sm/6 transition-colors duration-200"
            >
                <span
                    class="col-start-1 row-start-1 flex items-center gap-3 pr-6"
                >
                    <template v-if="selected">
                        <img
                            :src="
                                selected.avatar === null
                                    ? '/images/default-avatar.jpg'
                                    : selected.avatar
                            "
                            alt="Avatar of a selected user"
                            class="size-5 shrink-0 rounded-full"
                        />
                        <span class="block truncate">{{ selected.name }}</span>
                    </template>
                    <template v-else>
                        <span class="block truncate text-zinc-400">{{
                            placeholderText
                        }}</span>
                    </template>
                </span>
                <ChevronUpDownIcon
                    class="col-start-1 row-start-1 size-5 self-center justify-self-end text-zinc-400 sm:size-4"
                    aria-hidden="true"
                />
            </ListboxButton>

            <transition
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <ListboxOptions
                    class="relative z-10 mt-2 max-h-56 w-full overflow-auto rounded-md bg-inputfield-background py-1 text-base shadow-lg ring-1 ring-inputfield-stroke focus:outline-hidden sm:text-sm"
                >
                    <ListboxOption
                        as="template"
                        v-for="option in options"
                        :key="option.id"
                        :value="option"
                        v-slot="{ active, selected }"
                    >
                        <li
                            :class="[
                                active
                                    ? 'bg-zinc-800 text-zinc-100 outline-hidden'
                                    : 'text-zinc-300',
                                'relative cursor-default py-2 pr-9 pl-3 select-none transition-all duration-100',
                            ]"
                        >
                            <div class="flex items-center">
                                <img
                                    :src="
                                        option.avatar === null
                                            ? '/images/default-avatar.jpg'
                                            : option.avatar
                                    "
                                    alt="Avatar of a user"
                                    class="size-5 shrink-0 rounded-full"
                                />
                                <span
                                    :class="[
                                        selected
                                            ? 'font-semibold'
                                            : 'font-normal',
                                        'ml-3 block truncate',
                                    ]"
                                    >{{ option.name }}</span
                                >
                            </div>

                            <span
                                v-if="selected"
                                :class="[
                                    active ? 'text-zinc-100' : 'text-zinc-400',
                                    'absolute inset-y-0 right-0 flex items-center pr-4',
                                ]"
                            >
                                <CheckIcon class="size-5" aria-hidden="true" />
                            </span>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>

<script setup>
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from "@headlessui/vue";
import { ChevronUpDownIcon } from "@heroicons/vue/16/solid";
import { CheckIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    label: {
        type: String,
        default: "Select option",
    },
    options: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [Object, null],
        default: null,
    },
    placeholderText: {
        type: String,
        default: "No one is assigned",
    },
});

const emit = defineEmits(["update:modelValue"]);

const selected = defineModel();
</script>
