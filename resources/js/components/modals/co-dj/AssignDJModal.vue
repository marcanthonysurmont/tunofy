<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="emits('closeModal')"
        @submit-from-enter="assignDJ"
    >
        <template #title>
            <h1 class="text-4xl">Assign a co-DJ</h1>
            <p class="text-base text-zinc-300">
                The assigned co-DJ will be able to control playback.
            </p>
        </template>
        <template #body>
            <div class="mb-6 transition-all duration-300">
                <ListboxAvatar
                    v-model="selectedPerson"
                    :options="people"
                    label="Assigned to"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="assignDJ"
                :loading="isLoading"
                >Assign as co-DJ
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import ListboxAvatar from "@/components/forms/ListboxAvatar.vue";

const props = defineProps({
    isVisible: Boolean,
});

const page = usePage();
const mix = computed(() => page.props.mix);
const people = computed(() => page.props.collaborators);

//look for the selected person in the list of people
const selectedPerson = ref(
    people.value.find((person) => person.id === mix.value.co_dj_id) || null
);

//emit to close modal
const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function assignDJ() {
    //prevent spam clicks while loading
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;

    //
}
</script>
