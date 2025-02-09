<template>
        <form @submit.prevent="handleSubmit">
            <div v-if="currentStep === 1">
                <!-- Schritt 1: Basisinformationen -->
                <div class="mb-3">
                    <label for="event" class="form-label">Veranstaltung</label>
                    <input
                        type="text"
                        class="form-control"
                        id="event"
                        v-model="form.event"
                        required />
                    <span v-if="errors.event" class="text-danger">{{ errors.event }}</span>
                </div>

                <div class="mb-3">
                    <label for="event_start" class="form-label">Veranstaltungsbeginn</label>
                    <input
                        type="date"
                        class="form-control"
                        id="event_start"
                        v-model="form.event_start"
                        required />
                    <span v-if="errors.event_start" class="text-danger">{{ errors.event_start }}</span>
                </div>

                <div class="mb-3">
                    <label for="event_end" class="form-label">Veranstaltungsende</label>
                    <input
                        type="date"
                        class="form-control"
                        id="event_end"
                        v-model="form.event_end"
                        required />
                    <span v-if="errors.event_end" class="text-danger">{{ errors.event_end }}</span>
                </div>

                <button type="button" class="btn btn-primary" @click="goToStep(2)">
                    Weiter
                </button>
            </div>

            <div v-if="currentStep === 2">
                <!-- Benutzer Dropdown -->
                <div class="mb-3">
                    <label for="user_id" class="form-label">Benutzer</label>
                    <select
                        class="form-control"
                        id="user_id"
                        v-model="form.user_id"
                        required>
                        <option value="">Bitte Benutzer auswählen</option>
                        <option v-for="user in this.users" :key="user.id" :value="user.id">
                            {{ user.scoutname }}
                        </option>
                    </select>
                    <span v-if="errors.user_id" class="text-danger">{{ errors.user_id }}</span>
                </div>

                <div class="mb-3">
                    <label for="unit_id" class="form-label">Einheit</label>
                    <select
                        class="form-control"
                        id="unit_id"
                        v-model="form.unit_id"
                        required>
                        <option value="">Bitte Einheit auswählen</option>
                        <option v-for="unit in this.units" :key="unit.id" :value="unit.id">
                            {{ unit.name }}
                        </option>
                    </select>
                    <span v-if="errors.unit_id" class="text-danger">{{ errors.unit_id }}</span>
                </div>

                <!-- Schritt 2: Übergabe und Rückgabe -->
                <div class="mb-3">
                    <label for="handover_date" class="form-label">Übergabedatum</label>
                    <input
                        type="date"
                        class="form-control"
                        id="handover_date"
                        v-model="form.handover_date"
                        required
                    />
                    <span v-if="errors.handover_date" class="text-danger">{{ errors.handover_date }}</span>
                </div>

                <div class="mb-3">
                    <label for="return_date" class="form-label">Rückgabedatum</label>
                    <input
                        type="date"
                        class="form-control"
                        id="return_date"
                        v-model="form.return_date"
                        required
                    />
                    <span v-if="errors.return_date" class="text-danger">{{ errors.return_date }}</span>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" v-model="form.status" required>
                        <option value="open">Offen</option>
                        <option value="in_progress">In Bearbeitung</option>
                        <option value="closed">Abgeschlossen</option>
                    </select>
                    <span v-if="errors.status" class="text-danger">{{ errors.status }}</span>
                </div>

                <button type="button" class="btn btn-secondary" @click="goToStep(1)">
                    Zurück
                </button>
                <button type="submit" class="btn btn-primary">Abschließen</button>
            </div>
        </form>
</template>

<script>
export default {
    props: {
        createUrl: { type: String, required: true },
        editUrl: { type: String, required: true },
        order: { type: Object, default: () => ({}) },
        step: { type: Number, default: 1 },
        users: { type: Object, default: () => ({}) },
        units: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            currentStep: this.step,
            form: {
                event: this.order.event || "",
                event_start: this.order.event_start || "",
                event_end: this.order.event_end || "",
                handover_date: this.order.handover_date || "",
                return_date: this.order.return_date || "",
                status: this.order.status || "open",
                user_id: this.order.user_id || "",
                unit_id: this.order.unit_id || "",
            },
            errors: {},
        };
    },
    methods: {
        goToStep(step) {
            if (step === 2 && !this.validateStep1()) {
                return;
            }
            this.currentStep = step;
        },
        validateStep1() {
            // Schritt 1 Validierungslogik
            this.errors = {};
            if (!this.form.event) {
                this.errors.event = "Die Veranstaltung ist erforderlich.";
            }
            if (!this.form.event_start) {
                this.errors.event_start = "Das Veranstaltungsbeginn-Datum ist erforderlich.";
            }
            if (!this.form.event_end) {
                this.errors.event_end = "Das Veranstaltungsende-Datum ist erforderlich.";
            } else if (this.form.event_end < this.form.event_start) {
                this.errors.event_end = "Das Enddatum darf nicht vor dem Startdatum liegen.";
            }

            return Object.keys(this.errors).length === 0;
        },
        handleSubmit() {
            const url = this.currentStep === 1 ? this.createUrl : this.editUrl;

            this.errors = {};
            if (!this.form.handover_date) {
                this.errors.handover_date = "Das Übergabedatum ist erforderlich.";
            }
            if (!this.form.return_date) {
                this.errors.return_date = "Das Rückgabedatum ist erforderlich.";
            } else if (this.form.return_date < this.form.handover_date) {
                this.errors.return_date = "Das Rückgabedatum darf nicht vor dem Übergabedatum liegen.";
            }

            axios.post(url, {...this.form, step: this.currentStep})
                .then((response) => {
                    alert("Erfolgreich gespeichert!");
                })
                .catch((error) => {
                    if (error.response && error.response.data.errors) {
                        this.errors = error.response.data.errors;
                    }
                });
        },
    },
};
</script>

<style scoped>
/* Optional: einfache Stilanpassungen */
.text-danger {
    font-size: 0.875rem;
}
</style>
