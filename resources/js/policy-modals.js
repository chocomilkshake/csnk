/**
 * Legal Notice and Privacy Policy Modal Handler
 * Handles opening modals from footer links
 */

document.addEventListener('DOMContentLoaded', function() {
  // Get footer links
  const legalLinks = document.querySelectorAll('a[href="legal-notice.php"]');
  const privacyLinks = document.querySelectorAll('a[href="privacy-policy.php"]');

  // Intercept links in footer to open modals instead
  const footer = document.querySelector('footer');
  
  if (footer) {
    const footerLegalLink = footer.querySelector('a[href="legal-notice.php"]');
    const footerPrivacyLink = footer.querySelector('a[href="privacy-policy.php"]');

    if (footerLegalLink) {
      footerLegalLink.addEventListener('click', function(e) {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('legalModal'));
        modal.show();
      });
    }

    if (footerPrivacyLink) {
      footerPrivacyLink.addEventListener('click', function(e) {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('privacyModal'));
        modal.show();
      });
    }
  }
});
