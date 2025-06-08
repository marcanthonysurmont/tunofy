<template>
    <div>
        <label :for="id" class="block text-sm/6 font-medium text-white">
            {{ label }}
        </label>
        <div class="mt-2">
            <div v-if="type === 'profile'" class="sm:flex items-center gap-x-3">
                <div class="flex justify-center mb-3 sm:mb-0">
                    <div
                        v-if="imagePreview"
                        class="size-16 sm:size-12 rounded-full overflow-hidden"
                    >
                        <img
                            :src="imagePreview"
                            :alt="label"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div
                        v-else
                        class="size-16 sm:size-12 rounded-full flex items-center justify-center bg-[#2C2C2C]"
                    >
                        <UserCircleIcon
                            class="size-12 sm:size-10 text-zinc-500"
                            aria-hidden="true"
                        />
                    </div>
                </div>
                <div class="flex gap-2 justify-center sm:justify-start">
                    <label
                        :for="`${id}-input`"
                        class="rounded-md bg-[#2C2C2C] px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3C3C3C] cursor-pointer"
                    >
                        {{ imagePreview ? "Change" : "Upload" }}
                    </label>
                    <button
                        v-if="imagePreview"
                        type="button"
                        @click="removeImage"
                        class="rounded-md bg-image-upload-button-background px-2.5 py-1.5 text-sm font-semibold text-red-400 shadow-sm hover:bg-[#3C3C3C]"
                    >
                        Remove
                    </button>
                </div>
                <input
                    ref="fileInputRef"
                    :id="`${id}-input`"
                    type="file"
                    :accept="acceptedFileTypes"
                    @change="handleFileUpload"
                    class="sr-only"
                />
            </div>

            <div
                v-else
                :class="[
                    'flex justify-center rounded-md px-4 sm:px-6 py-6 sm:py-8 outline-1 -outline-offset-1 transition-colors focus-visible:outline-2 focus-visible:outline-primary focus-visible:-outline-offset-1',
                    isDragging
                        ? 'outline-primary bg-[#1A1A1A]/80'
                        : 'outline-inputfield-stroke bg-inputfield-background',
                    hasError ? 'outline-red-500' : '',
                ]"
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
            >
                <div v-if="imagePreview" class="w-full">
                    <div class="relative">
                        <img
                            :src="imagePreview"
                            :alt="label"
                            class="w-full max-h-48 object-cover rounded-sm"
                        />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute top-2 right-2 rounded-full bg-black/90 p-1 text-white hover:bg-black cursor-pointer focus-visible:outline-4 focus-visible:outline-primary focus-visible:-outline-offset-1"
                        >
                            <XMarkIcon class="size-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
                <div v-else class="text-center w-full">
                    <PhotoIcon
                        class="mx-auto size-12 text-zinc-500"
                        aria-hidden="true"
                    />
                    <div
                        class="mt-4 flex flex-col sm:flex-row justify-center items-center text-sm/6 text-zinc-300"
                    >
                        <label
                            tabindex="0"
                            role="button"
                            @keydown.enter.prevent="triggerFileInput"
                            :for="`${id}-input`"
                            class="relative cursor-pointer rounded-md bg-image-upload-button-background px-2 py-1 font-semibold text-white hover:bg-[#3C3C3C] mb-2 sm:mb-0 border-1 border-image-upload-button-stroke focus-visible:outline-2 focus-visible:outline-primary focus-visible:-outline-offset-1"
                        >
                            <input
                                ref="fileInputRef"
                                :id="`${id}-input`"
                                type="file"
                                :accept="acceptedFileTypes"
                                @change="handleFileUpload"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                            />
                            <span>Upload a file</span>
                        </label>
                        <p class="sm:pl-1 self-center hidden sm:inline">
                            or drag and drop
                        </p>
                    </div>
                    <p class="text-xs/5 text-zinc-400 mt-2">
                        {{ helperText || "PNG or JPG" }}
                    </p>
                </div>
            </div>
        </div>
        <p
            v-if="hasError"
            class="mt-2 text-sm text-red-500"
            :id="`${id}-error`"
        >
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { UserCircleIcon, PhotoIcon, XMarkIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    modelValue: [File, String, null],
    label: String,
    id: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: "cover",
        validator: (value) => ["profile", "cover"].includes(value),
    },
    acceptedFileTypes: {
        type: String,
        default: "image/png, image/jpeg",
    },
    maxSizeInMB: {
        type: Number,
        default: 10,
    },
    helperText: String,
    error: {
        type: String,
        default: "",
    },
});

const fileInputRef = ref(null);
function triggerFileInput() {
    console.log("triggerFileInput called", fileInputRef.value);
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
}

const emit = defineEmits(["update:modelValue", "error"]);

const hasError = computed(() => !!props.error);
const isDragging = ref(false);
const imagePreview = ref(null);

//handle existing image (from modelValue) via watcher
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue instanceof File) {
            createPreview(newValue);
        } else if (typeof newValue === "string" && newValue) {
            imagePreview.value = newValue;
        } else {
            imagePreview.value = null;
        }
    },
    { immediate: true }
);

//file handling functions
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    //clear any existing error when attempting to upload a new file
    clearError();

    if (!validateFile(file)) {
        return;
    }

    createPreview(file);
    emit("update:modelValue", file);
}

function onDrop(event) {
    isDragging.value = false;

    const file = event.dataTransfer.files[0];
    if (!file) {
        return;
    }

    //clear any existing error when attempting to upload a new file
    clearError();

    if (!validateFile(file)) {
        return;
    }

    createPreview(file);
    emit("update:modelValue", file);
}

function validateFile(file) {
    //check file type
    const fileTypes = props.acceptedFileTypes
        .split(",")
        .map((type) => type.trim());
    if (!fileTypes.includes(file.type)) {
        emit(
            "error",
            "Invalid file type. Please upload an accepted image format."
        );
        return false;
    }

    //check file size
    const maxSizeInBytes = props.maxSizeInMB * 1024 * 1024;
    if (file.size > maxSizeInBytes) {
        emit(
            "error",
            `File too large. Maximum size is ${props.maxSizeInMB}MB.`
        );
        return false;
    }

    return true;
}

function createPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    imagePreview.value = null;
    emit("update:modelValue", null);
    clearError();
}

//function to clear errors
function clearError() {
    if (props.error) {
        emit("error", "");
    }
}
</script>
