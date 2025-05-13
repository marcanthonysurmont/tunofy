<template>
    <Combobox v-model="selected">
        <div class="relative mt-2">
            <div
                class="relative w-full cursor-default overflow-hidden rounded-md bg-zinc-800 text-left border border-zinc-600 focus-within:ring-2 focus-within:ring-zinc-500 sm:text-sm"
            >
                <ComboboxInput
                    class="w-full border-none py-2 pl-3 pr-10 text-sm text-zinc-200 bg-zinc-800 placeholder-zinc-400 focus:ring-0"
                    placeholder="Select a person..."
                    :displayValue="(person) => person.name"
                    @change="query = $event.target.value"
                />
                <ComboboxButton
                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                >
                    <ChevronUpDownIcon
                        class="h-4 w-4 text-zinc-400"
                        aria-hidden="true"
                    />
                </ComboboxButton>
            </div>

            <TransitionRoot
                leave="transition ease-in duration-75"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
                @after-leave="query = ''"
            >
                <ComboboxOptions
                    class="absolute z-10 mt-1 w-full overflow-auto rounded-md bg-zinc-800 py-1 text-sm shadow ring-1 ring-zinc-600 focus:outline-none custom-scrollbar sm:max-h-72 max-h-52"
                >
                    <div
                        v-if="filteredPeople.length === 0 && query !== ''"
                        class="cursor-default select-none px-4 py-2 text-zinc-400"
                    >
                        Nothing found.
                    </div>

                    <ComboboxOption
                        v-for="person in filteredPeople"
                        as="template"
                        :key="person.id"
                        :value="person"
                        v-slot="{ selected, active }"
                    >
                        <li
                            class="relative cursor-default select-none py-2 pl-10 pr-4 transition-colors"
                            :class="{
                                'bg-zinc-600 text-zinc-200': active,
                                'text-zinc-300': !active,
                            }"
                        >
                            <span
                                class="block truncate"
                                :class="{
                                    'font-medium': selected,
                                    'font-normal': !selected,
                                }"
                            >
                                {{ person.name }}
                            </span>
                            <span
                                v-if="selected"
                                class="absolute inset-y-0 left-0 flex items-center pl-3"
                                :class="{
                                    'text-zinc-200': active,
                                    'text-zinc-500': !active,
                                }"
                            >
                                <CheckIcon class="h-4 w-4" aria-hidden="true" />
                            </span>
                        </li>
                    </ComboboxOption>
                </ComboboxOptions>
            </TransitionRoot>
        </div>
    </Combobox>
</template>

<script setup>
import { ref, computed } from "vue";
import {
    Combobox,
    ComboboxInput,
    ComboboxButton,
    ComboboxOptions,
    ComboboxOption,
    TransitionRoot,
} from "@headlessui/vue";
import { CheckIcon, ChevronUpDownIcon } from "@heroicons/vue/20/solid";

const people = [
    { id: 1, name: "Wade Cooper" },
    { id: 2, name: "Arlene Mccoy" },
    { id: 3, name: "Devon Webb" },
    { id: 4, name: "Tom Cook" },
    { id: 5, name: "Tanya Fox" },
    { id: 6, name: "Hellen Schmidt" },
];

const selected = ref(people[0]);
const query = ref("");

// Computed property for filtered people
const filteredPeople = computed(() =>
    query.value === ""
        ? people
        : people.filter((person) =>
              person.name
                  .toLowerCase()
                  .replace(/\s+/g, "")
                  .includes(query.value.toLowerCase().replace(/\s+/g, ""))
          )
);
</script>
