<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="
            emits('closeModal');
            clearForm();
        "
        @submit-from-enter="storeMix"
    >
        <template #title>
            <h1 class="text-4xl">Import from Spotify</h1>
        </template>
        <template #body>
            <div class="mb-6">
                <InputField
                    v-model="userInput"
                    label="Playlist link"
                    id="link"
                    name="link"
                    type="link"
                    placeholder="https://open.spotify.com/playlist/xxxxxx"
                    :error="form.errors.playlist_id"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="storeMix"
                :loading="form.processing"
                :disabled="form.processing"
                >Import Playlist
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import InputField from "@/components/forms/InputField.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    isVisible: Boolean,
    mix: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    playlist_id: null,
});

//emit to close modal
const emits = defineEmits(["closeModal"]);
const userInput = ref(null);

function storeMix() {
    if (userInput.value === null) {
        form.setError("playlist_id", "The playlist link field is required.");
        return;
    }
    form.playlist_id = extractSpotifyPlaylistId(userInput.value);
    if (form.playlist_id === null) {
        form.setError("playlist_id", "Invalid Spotify playlist URL.");
        return;
    }
    form.clearErrors("playlist_id");
    form.post(route("mix.import-spotify-playlist", props.mix.id), {
        onSuccess: () => {
            emits("closeModal");
            clearForm();
        },
        onError: (errors) => {
            console.log(errors);
        },
    });
}

function clearForm() {
    form.reset();
    userInput.value = null;
    form.clearErrors();
}

function extractSpotifyPlaylistId(url) {
    const regex = /playlist\/([a-zA-Z0-9]+)(\?|$)/;
    const match = url.match(regex);
    return match ? match[1] : null;
}
</script>
