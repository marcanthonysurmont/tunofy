<template>
    <TransitionRoot as="template" :show="isVisible">
        <Dialog as="div" class="relative z-[9999]" @close="closeDrawer">
            <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div
                        class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full sm:pl-10"
                    >
                        <TransitionChild
                            as="template"
                            enter="transform transition ease-in-out duration-300"
                            enter-from="translate-x-full"
                            enter-to="translate-x-0"
                            leave="transform transition ease-in-out duration-300"
                            leave-from="translate-x-0"
                            leave-to="translate-x-full"
                        >
                            <DialogPanel
                                class="pointer-events-auto w-screen max-w-md"
                            >
                                <div
                                    class="flex h-full flex-col bg-background-page px-6 py-8 shadow-xl"
                                >
                                    <div class="absolute top-4 right-4">
                                        <button
                                            @click="closeDrawer"
                                            class="text-white hover:text-gray-300"
                                        >
                                            <XMarkIcon class="w-6 h-6" />
                                        </button>
                                    </div>
                                    <template v-if="mix.co_dj_id === null">
                                        <div
                                            class="flex flex-row items-start mb-6"
                                        >
                                            <div class="mt-6">
                                                <h1
                                                    class="text-3xl flex items-center gap-2"
                                                >
                                                    Assign Co-DJ
                                                </h1>
                                                <p
                                                    class="text-red-400 text-sm flex items-center gap-1 mt-2 leading-snug"
                                                >
                                                    <ExclamationCircleIcon
                                                        class="size-5 shrink-0"
                                                    />
                                                    <span
                                                        >Search results are
                                                        exclusively for premium
                                                        users.</span
                                                    >
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col h-full">
                                            <SearchBarUsers
                                                :premium-filter="true"
                                                @clear-search="
                                                    searchResult = {};
                                                    isSearching = false;
                                                    selectedDJ = null;
                                                "
                                                @search-updated="
                                                    searchResult = $event;
                                                    selectedDJ = null;
                                                "
                                                @is-searching="
                                                    isSearching = $event
                                                "
                                            />

                                            <ul
                                                class="relative flex flex-col gap-3 mt-1 overflow-y-auto custom-scrollbar max-h-[50vh]"
                                            >
                                                <template
                                                    v-if="
                                                        searchResult.length > 0
                                                    "
                                                    v-auto-animate
                                                >
                                                    <li
                                                        v-for="user in searchResult"
                                                        :key="user.id"
                                                        class="flex items-center justify-between py-2 rounded-lg cursor-pointer hover:bg-zinc-800 transition px-4"
                                                        @click.stop="
                                                            selectDJ(user)
                                                        "
                                                    >
                                                        <div
                                                            class="flex items-center gap-3"
                                                        >
                                                            <img
                                                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-full"
                                                                :src="
                                                                    user.avatar_url
                                                                "
                                                                alt="User"
                                                            />
                                                            <p
                                                                class="text-sm sm:text-base text-white truncate max-w-[60vw] sm:max-w-none"
                                                            >
                                                                {{ user.name }}
                                                            </p>
                                                        </div>

                                                        <div
                                                            class="w-6 h-6 rounded-full border flex items-center justify-center cursor-pointer hover:border-primary transition"
                                                            :class="
                                                                selectedDJ?.id ===
                                                                user.id
                                                                    ? 'border-primary'
                                                                    : 'border-zinc-700'
                                                            "
                                                        >
                                                            <CheckIcon
                                                                v-if="
                                                                    selectedDJ?.id ===
                                                                    user.id
                                                                "
                                                                class="w-4 h-4 text-primary"
                                                            />
                                                        </div>
                                                    </li>
                                                </template>
                                                <p
                                                    v-else-if="
                                                        searchResult.length ===
                                                        0
                                                    "
                                                >
                                                    No results found..
                                                </p>
                                            </ul>

                                            <div
                                                v-if="searchResult.length > 0"
                                                class="mt-auto pt-4"
                                            >
                                                <RegularButton
                                                    @click="assignCoDJ"
                                                    :disabled="
                                                        selectedDJ === null
                                                    "
                                                    :loading="form.processing"
                                                >
                                                    Assign as Co-DJ
                                                </RegularButton>
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div
                                            class="flex flex-col items-center justify-center h-full"
                                        >
                                            <h1 class="text-3xl">
                                                Take back control!
                                            </h1>
                                            <p
                                                class="text-zinc-300 text-sm mt-2 mb-6"
                                            >
                                                Currently, Gilles Serrien is the
                                                Co-DJ of this mix.
                                            </p>
                                            <RegularButton
                                                @click="takeBackControl"
                                                :loading="form.processing"
                                            >
                                                Take back control
                                            </RegularButton>
                                        </div>
                                    </template>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
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
    XMarkIcon,
    ExclamationCircleIcon,
    CheckIcon,
} from "@heroicons/vue/24/solid";
import { useForm } from "@inertiajs/vue3";
import SearchBarUsers from "@/components/subpages/manage/SearchBarUsers.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";

const props = defineProps({
    isVisible: Boolean,
    mix: Object,
});

const emit = defineEmits(["close-drawer"]);

const searchResult = ref({});
const isSearching = ref(false);
const selectedDJ = ref(null);

const form = useForm({
    user_id: null,
});

function closeDrawer() {
    searchResult.value = {};
    selectedDJ.value = null;
    isSearching.value = false;
    form.reset();
    emit("close-drawer");
}

function takeBackControl() {
    form.post(
        route("mix.remove-co-dj", props.mix.id),
        { preserveScroll: true },
        {
            onSuccess: () => closeDrawer(),
        }
    );
}

function assignCoDJ() {
    if (!selectedDJ.value || form.processing) return;

    form.user_id = selectedDJ.value.id;

    form.post(
        route("mix.assign-co-dj", props.mix.id),
        { preserveScroll: true },
        {
            onSuccess: () => closeDrawer(),
        }
    );
}

function selectDJ(user) {
    if (selectedDJ.value?.id === user.id) {
        selectedDJ.value = null;
    } else {
        selectedDJ.value = user;
    }
}
</script>
