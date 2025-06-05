<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed left-0 top-0 z-[9999] h-screen w-screen px-6 py-8 bg-background-page backdrop-blur-md shadow-2xl flex flex-col"
            >
                <span
                    class="cursor-pointer text-xl absolute right-3 top-3 sm:right-4 sm:top-4"
                    @click="closeModal"
                >
                    <XMarkIcon class="size-12 p-2" />
                </span>

                <template v-if="mix.co_dj_id === null">
                    <div class="flex flex-row items-start mb-6">
                        <div class="mt-6">
                            <h1 class="text-3xl flex items-center gap-2">
                                Assign Co-DJ
                            </h1>
                            <p
                                class="text-red-400 text-sm flex items-center gap-1 mt-2 leading-snug"
                            >
                                <ExclamationCircleIcon
                                    class="size-5 shrink-0"
                                />
                                <span v-if="users.length > 0"
                                    >Search results are exclusively for premium
                                    users.</span
                                >
                                <span v-else
                                    >No available premium users found in
                                    mix.</span
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
                            @is-searching="isSearching = $event"
                        />

                        <ul
                            class="relative flex flex-col gap-3 mt-1 overflow-y-auto custom-scrollbar max-h-[50vh]"
                        >
                            <template
                                v-if="searchResult.length > 0"
                                v-auto-animate
                            >
                                <li
                                    v-for="user in searchResult"
                                    :key="user.id"
                                    class="flex items-center justify-between py-2 rounded-lg"
                                    @click.stop="selectDJ(user)"
                                >
                                    <div class="flex items-center gap-3">
                                        <img
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover"
                                            :src="user.avatar_url"
                                            alt="Image of user's avatar"
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
                                            selectedDJ?.id === user.id
                                                ? 'border-primary'
                                                : 'border-zinc-700'
                                        "
                                    >
                                        <CheckIcon
                                            v-if="selectedDJ?.id === user.id"
                                            class="w-4 h-4 text-primary"
                                        />
                                    </div>
                                </li>
                            </template>
                            <p v-else-if="searchResult.length === 0">
                                No results found..
                            </p>
                        </ul>

                        <div
                            v-if="searchResult.length > 0"
                            class="mt-auto pt-4"
                        >
                            <RegularButton
                                @click="assignCoDJ"
                                :disabled="selectedDJ === null"
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
                        <h1 class="text-3xl">Take back control!</h1>
                        <p class="text-zinc-300 text-sm mt-2 mb-6">
                            Currently, Gilles Serrien is the Co-DJ of this mix.
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
        </transition>
    </Teleport>
</template>

<script setup>
import { XMarkIcon, ExclamationCircleIcon } from "@heroicons/vue/24/solid";
import SearchBarUsers from "@/components/subpages/manage/SearchBarUsers.vue";
import { CheckIcon } from "@heroicons/vue/24/solid";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false,
    },
    mix: {
        type: Object,
    },
});

const page = usePage();
const users = computed(() => page.props.collaborators.data);

const emit = defineEmits(["update:selected-dj", "close-modal", "save-co-dj"]);

const searchResult = ref({});
const isSearching = ref(false);
const selectedDJ = ref(null);

const form = useForm({
    user_id: null,
});

function takeBackControl() {
    form.post(
        route("mix.remove-co-dj", props.mix.id),
        {
            preserveScroll: true,
        },
        {
            onSuccess: () => {
                closeModal();
            },
        }
    );
}

function assignCoDJ() {
    if (!selectedDJ.value || form.processing) {
        return;
    }

    form.user_id = selectedDJ.value.id;

    form.post(
        route("mix.assign-co-dj", props.mix.id),
        {
            preserveScroll: true,
        },
        {
            onSuccess: () => {
                closeModal();
            },
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

function closeModal() {
    emit("close-modal");
}
</script>
