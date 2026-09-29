export const NAME_MAX_LENGTH = 64;

/**
 * Restricts a relationship name to what the server accepts: lowercase letters,
 * numbers, dashes and underscores, up to 64 characters.
 *
 * Spaces become dashes. Leading and trailing dashes are kept so a name can be
 * typed one character at a time.
 */
export const sanitizeName = (value: string): string =>
	value
		.toLowerCase()
		.normalize('NFD')
		.replace(/[̀-ͯ]/g, '')
		.replace(/\s+/g, '-')
		.replace(/[^a-z0-9_-]/g, '')
		.slice(0, NAME_MAX_LENGTH);
