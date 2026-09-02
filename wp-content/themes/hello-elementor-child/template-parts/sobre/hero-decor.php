<?php
/**
 * The oversized "About Us" wordmark on the right of the hero.
 *
 * The Figma original uses a "Scanline warp" effect. This is a CSS
 * approximation: the white type is masked by a repeating horizontal stripe
 * gradient that opens up towards the left, so the letters dissolve into lines
 * the way they do in the design. The warp itself (the wave distortion) has no
 * CSS equivalent — if it has to be exact, the word needs exporting as an image
 * from Figma and dropping into assets/img.
 */
?>
<div class="sobre-hero__decor" aria-hidden="true">
	<span class="sobre-hero__word">About</span>
	<span class="sobre-hero__word sobre-hero__word--baixo">Us</span>
</div>
