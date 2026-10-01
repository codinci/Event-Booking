<template>
	<div class="flex flex-col items-center justify-center gap-6">
		<h1 class="text-4xl font-bold">Welcome to Event Booking</h1>
		<p class="text-lg text-muted-foreground">
			This is a simple event booking application built with Laravel and Vue.js.
		</p>
		<div class="flex gap-4">
			<router-link
				to="/login"
				class="rounded-lg bg-primary px-4 py-2 text-white hover:bg-primary/90"
			>
				Login
			</router-link>
			<router-link
				to="/register"
				class="rounded-lg bg-secondary px-4 py-2 text-white hover:bg-secondary/90"
			>
				Register
			</router-link>
		</div>
	</div>
</template>