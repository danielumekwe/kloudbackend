<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            [
                'slug'           => 'privacy-policy',
                'title'          => 'Privacy Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Privacy Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>Kloud101 Limited (&ldquo;Kloud101&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;, or &ldquo;our&rdquo;) is committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and cloud hosting services.</p>

<h2>1. Information We Collect</h2>
<h3>1.1 Information You Provide</h3>
<ul>
  <li><strong>Account data:</strong> name, email address, phone number, billing address.</li>
  <li><strong>Payment data:</strong> billing details processed through our PCI-compliant payment providers (Paystack, Flutterwave, NOWPayments). We do not store raw card numbers.</li>
  <li><strong>Support data:</strong> messages and attachments you send to our support team.</li>
  <li><strong>Identity verification:</strong> documents you provide to verify your identity where required by law.</li>
</ul>
<h3>1.2 Information We Collect Automatically</h3>
<ul>
  <li>IP address, browser type, device identifiers, and operating system.</li>
  <li>Pages visited, referring URLs, and session duration.</li>
  <li>Log data from servers and infrastructure you provision with us.</li>
</ul>
<h3>1.3 Cookies &amp; Tracking Technologies</h3>
<p>We use cookies and similar technologies as described in our <a href="/cookie-policy">Cookie Policy</a>.</p>

<h2>2. How We Use Your Information</h2>
<ul>
  <li>To provision and manage the services you have ordered.</li>
  <li>To process payments and send invoices.</li>
  <li>To send service notifications, security alerts, and support responses.</li>
  <li>To comply with legal obligations (e.g., NDPR, GDPR, anti-money laundering regulations).</li>
  <li>To improve our platform and troubleshoot issues.</li>
  <li>To send marketing communications where you have given consent (you may opt out at any time).</li>
</ul>

<h2>3. Legal Basis for Processing (GDPR / NDPR)</h2>
<ul>
  <li><strong>Contract performance:</strong> processing necessary to provide the services you ordered.</li>
  <li><strong>Legal obligation:</strong> complying with applicable laws.</li>
  <li><strong>Legitimate interest:</strong> fraud prevention, network security, and service improvement.</li>
  <li><strong>Consent:</strong> marketing emails and non-essential cookies — withdrawable at any time.</li>
</ul>

<h2>4. Sharing Your Information</h2>
<p>We do not sell your personal data. We may share it with:</p>
<ul>
  <li><strong>Service providers:</strong> hosting infrastructure, payment processors, email delivery, and analytics tools — all bound by data processing agreements.</li>
  <li><strong>Law enforcement:</strong> when required by a valid court order or applicable law.</li>
  <li><strong>Business transfers:</strong> in the event of a merger, acquisition, or asset sale, your data may transfer to the successor entity subject to the same protections.</li>
</ul>

<h2>5. International Transfers</h2>
<p>Your data may be processed in Nigeria and in other countries where our infrastructure partners operate. We ensure appropriate safeguards (standard contractual clauses or adequacy decisions) are in place for cross-border transfers.</p>

<h2>6. Data Retention</h2>
<p>We retain personal data for as long as your account is active and for a further period as required by law (typically 7 years for financial records). You may request deletion of non-legally-required data at any time.</p>

<h2>7. Your Rights</h2>
<p>Depending on your jurisdiction you have the right to:</p>
<ul>
  <li>Access the personal data we hold about you.</li>
  <li>Correct inaccurate data.</li>
  <li>Request deletion (&ldquo;right to be forgotten&rdquo;) where no legal retention obligation applies.</li>
  <li>Object to or restrict processing.</li>
  <li>Data portability — receive your data in a machine-readable format.</li>
  <li>Withdraw consent at any time without affecting prior lawful processing.</li>
  <li>Lodge a complaint with your local data protection authority.</li>
</ul>
<p>To exercise any right, contact us at <a href="mailto:privacy@kloud101.com">privacy@kloud101.com</a> or submit a request through your dashboard under <strong>Settings &rsaquo; Privacy &amp; Security</strong>.</p>

<h2>8. Security</h2>
<p>We implement industry-standard technical and organisational measures including encryption at rest and in transit, access controls, and regular security audits. No system is 100&nbsp;% secure; please notify us immediately at <a href="mailto:security@kloud101.com">security@kloud101.com</a> if you suspect unauthorised access.</p>

<h2>9. Children</h2>
<p>Our services are not directed to individuals under 18. We do not knowingly collect data from minors. If we become aware of such data it will be deleted promptly.</p>

<h2>10. Changes to This Policy</h2>
<p>We will notify registered users by email of material changes at least 30 days before they take effect. Continued use of our services after the effective date constitutes acceptance.</p>

<h2>11. Contact</h2>
<p>Kloud101 Limited &bull; Lagos, Nigeria<br>Email: <a href="mailto:privacy@kloud101.com">privacy@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'terms-of-service',
                'title'          => 'Terms of Service',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Terms of Service</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>These Terms of Service (&ldquo;Terms&rdquo;) govern your access to and use of the services provided by Kloud101 Limited (&ldquo;Kloud101&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;). By creating an account or using our services you agree to these Terms.</p>

<h2>1. Eligibility</h2>
<p>You must be at least 18 years old and capable of forming a binding contract to use our services. By agreeing to these Terms you represent that you meet these requirements.</p>

<h2>2. Account Registration</h2>
<ul>
  <li>You must provide accurate, current, and complete information during registration.</li>
  <li>You are responsible for maintaining the confidentiality of your credentials.</li>
  <li>You must notify us immediately of any unauthorised access at <a href="mailto:support@kloud101.com">support@kloud101.com</a>.</li>
  <li>One person or legal entity may not maintain more than one free-tier account.</li>
</ul>

<h2>3. Services</h2>
<p>Kloud101 provides cloud hosting, VPS, dedicated servers, SSL certificates, domain registration, and related infrastructure services (&ldquo;Services&rdquo;). Service specifications, including uptime commitments, are described in our <a href="/service-level-agreement">Service Level Agreement</a>.</p>

<h2>4. Payment &amp; Billing</h2>
<ul>
  <li>All fees are due in advance unless otherwise agreed in writing.</li>
  <li>Invoices unpaid after the due date may result in service suspension after a 3-day grace period.</li>
  <li>Prices are shown in your selected currency. Exchange rates are applied at the time of invoicing.</li>
  <li>Taxes (including VAT and applicable Nigerian levies) are added where required by law.</li>
  <li>Refunds are governed by our <a href="/refund-policy">Refund Policy</a>.</li>
</ul>

<h2>5. Acceptable Use</h2>
<p>You agree to use our Services in accordance with our <a href="/acceptable-use-policy">Acceptable Use Policy</a>. Violations may result in immediate suspension or termination without refund.</p>

<h2>6. Content &amp; Data</h2>
<ul>
  <li>You retain ownership of all data you store on our infrastructure.</li>
  <li>You grant Kloud101 the right to host, transmit, and backup that data solely to provide the Services.</li>
  <li>You are solely responsible for the legality of content you host.</li>
</ul>

<h2>7. Intellectual Property</h2>
<p>All trademarks, logos, and software comprising the Kloud101 platform remain our exclusive property. Nothing in these Terms transfers ownership of our intellectual property to you.</p>

<h2>8. Confidentiality</h2>
<p>Each party agrees to keep the other&rsquo;s confidential information secret and to use it only as necessary to perform obligations under these Terms.</p>

<h2>9. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law, Kloud101&rsquo;s total liability arising from or relating to these Terms or the Services shall not exceed the fees you paid in the 3 months preceding the event giving rise to the claim. We are not liable for indirect, consequential, special, or punitive damages.</p>

<h2>10. Indemnification</h2>
<p>You agree to indemnify and hold harmless Kloud101 and its officers, employees, and agents from any claims, damages, or expenses (including reasonable legal fees) arising from your use of the Services, your violation of these Terms, or your violation of any third-party rights.</p>

<h2>11. Termination</h2>
<ul>
  <li>You may cancel your account at any time through your dashboard.</li>
  <li>We may suspend or terminate your account for breach of these Terms, non-payment, or legal obligation, with notice where practicable.</li>
  <li>On termination, your data will be available for export for 14 days, then deleted.</li>
</ul>

<h2>12. Governing Law</h2>
<p>These Terms are governed by the laws of the Federal Republic of Nigeria. Disputes shall be resolved in the courts of Lagos State, Nigeria, unless applicable law requires otherwise.</p>

<h2>13. Changes</h2>
<p>We may update these Terms. Material changes will be notified by email at least 30 days before they take effect. Continued use after the effective date constitutes acceptance.</p>

<h2>14. Contact</h2>
<p>Kloud101 Limited &bull; Lagos, Nigeria<br>Email: <a href="mailto:legal@kloud101.com">legal@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'acceptable-use-policy',
                'title'          => 'Acceptable Use Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Acceptable Use Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>This Acceptable Use Policy (&ldquo;AUP&rdquo;) applies to all services provided by Kloud101 Limited. By using our services you agree to comply with this AUP. Violations may result in immediate suspension or termination of services without refund.</p>

<h2>1. Prohibited Activities</h2>
<h3>1.1 Illegal Content</h3>
<p>You may not use our infrastructure to host, distribute, or facilitate content that is:</p>
<ul>
  <li>Child sexual abuse material (CSAM) or any content that sexually exploits minors.</li>
  <li>Content that violates applicable copyright, trademark, or other intellectual property laws.</li>
  <li>Content that facilitates human trafficking, terrorism, or other violent crimes.</li>
  <li>Defamatory, fraudulent, or deceptive content.</li>
</ul>

<h3>1.2 Network Abuse</h3>
<ul>
  <li>Launching or facilitating denial-of-service (DoS/DDoS) attacks against any target.</li>
  <li>Port scanning, network probing, or vulnerability exploitation without written authorisation from the target owner.</li>
  <li>Operating open mail relays or sending unsolicited bulk email (spam).</li>
  <li>Generating traffic levels that adversely affect other customers (bandwidth abuse).</li>
  <li>Attempting to gain unauthorised access to Kloud101 systems or those of other customers.</li>
</ul>

<h3>1.3 Malicious Software</h3>
<ul>
  <li>Hosting, distributing, or operating malware, ransomware, botnets, phishing kits, or command-and-control infrastructure.</li>
  <li>Cryptomining on shared or burstable resources beyond your purchased allocation.</li>
</ul>

<h3>1.4 Spam &amp; Phishing</h3>
<ul>
  <li>Sending unsolicited commercial email without recipient consent.</li>
  <li>Operating phishing websites or impersonating any person or organisation.</li>
  <li>Harvesting email addresses without authorisation.</li>
</ul>

<h2>2. Resource Usage</h2>
<ul>
  <li>You may not use more CPU, memory, disk I/O, or network resources than allocated to your plan.</li>
  <li>Shared hosting accounts may not run persistent background processes not related to web serving.</li>
  <li>Excessive resource usage that degrades the experience of other customers may result in throttling or suspension.</li>
</ul>

<h2>3. Security Responsibilities</h2>
<ul>
  <li>You are responsible for securing all software and applications you deploy.</li>
  <li>You must promptly apply security patches to software running on your servers.</li>
  <li>If your server is compromised and used for prohibited activities, we may suspend it without notice.</li>
</ul>

<h2>4. Reporting Abuse</h2>
<p>To report a violation of this policy, email <a href="mailto:abuse@kloud101.com">abuse@kloud101.com</a> with as much detail as possible. We investigate all credible reports and respond within 24 hours for urgent cases.</p>

<h2>5. Enforcement</h2>
<p>Kloud101 reserves the right, at our sole discretion, to:</p>
<ul>
  <li>Suspend or terminate services immediately for serious violations.</li>
  <li>Remove or disable access to content that violates this AUP.</li>
  <li>Report violations to law enforcement authorities.</li>
  <li>Cooperate fully with any lawful investigation.</li>
</ul>

<h2>6. Changes</h2>
<p>We may update this AUP at any time. Continued use of services constitutes acceptance of the updated policy.</p>

<h2>Contact</h2>
<p>Abuse reports: <a href="mailto:abuse@kloud101.com">abuse@kloud101.com</a><br>General: <a href="mailto:legal@kloud101.com">legal@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'cookie-policy',
                'title'          => 'Cookie Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Cookie Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>This Cookie Policy explains how Kloud101 Limited uses cookies and similar technologies on our website (<strong>kloud101.com</strong>) and client portal (<strong>my.kloud101.com</strong>).</p>

<h2>1. What Are Cookies?</h2>
<p>Cookies are small text files stored on your device by your browser when you visit a website. They allow the site to remember information about your visit. Similar technologies include local storage, session storage, and pixels.</p>

<h2>2. Cookies We Use</h2>
<h3>2.1 Strictly Necessary Cookies</h3>
<p>These cookies are essential for the website to function and cannot be disabled.</p>
<table>
  <thead><tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr></thead>
  <tbody>
    <tr><td>session</td><td>Maintains your authenticated session in the client portal</td><td>Session</td></tr>
    <tr><td>XSRF-TOKEN</td><td>Prevents cross-site request forgery attacks</td><td>Session</td></tr>
    <tr><td>kloud101-cookie-prefs</td><td>Stores your cookie preferences</td><td>1 year</td></tr>
  </tbody>
</table>

<h3>2.2 Analytics Cookies</h3>
<p>These help us understand how visitors use our site. All data is aggregated and anonymised.</p>
<table>
  <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
  <tbody>
    <tr><td>_ga, _ga_*</td><td>Google Analytics</td><td>Visitor and session counting</td><td>2 years</td></tr>
    <tr><td>_gid</td><td>Google Analytics</td><td>Distinguishes users</td><td>24 hours</td></tr>
  </tbody>
</table>

<h3>2.3 Marketing Cookies</h3>
<p>These allow us to show you relevant advertisements and track campaign performance.</p>
<table>
  <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
  <tbody>
    <tr><td>_fbp</td><td>Meta</td><td>Facebook advertising</td><td>3 months</td></tr>
    <tr><td>_gcl_au</td><td>Google</td><td>Google Ads conversion tracking</td><td>3 months</td></tr>
  </tbody>
</table>

<h2>3. Managing Your Preferences</h2>
<p>When you first visit our site, a cookie banner allows you to accept all cookies, reject non-essential cookies, or customise your preferences. You can update your choices at any time in your browser settings or by clearing your cookies and revisiting the site.</p>
<p>If you are a registered user you can also manage preferences under <strong>Settings &rsaquo; Privacy &amp; Security</strong> in your dashboard.</p>

<h2>4. Third-Party Cookies</h2>
<p>Some third-party services we embed (e.g., payment gateways, support widgets) may set their own cookies subject to their respective privacy policies.</p>

<h2>5. Changes</h2>
<p>We may update this Cookie Policy as we change the technologies we use. We will notify you of significant changes via the cookie banner on your next visit.</p>

<h2>Contact</h2>
<p>Questions about cookies: <a href="mailto:privacy@kloud101.com">privacy@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'refund-policy',
                'title'          => 'Refund Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Refund Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>This Refund Policy outlines the conditions under which Kloud101 Limited will issue refunds for services purchased through our platform.</p>

<h2>1. Money-Back Guarantee</h2>
<p>New customers ordering VPS or cloud server services for the first time are eligible for a full refund if requested within <strong>7 days</strong> of the initial order date, provided:</p>
<ul>
  <li>The server has been powered off and no data has been transferred outbound exceeding 50&nbsp;GB.</li>
  <li>The request is submitted via <a href="/support">support ticket</a> or email to <a href="mailto:billing@kloud101.com">billing@kloud101.com</a>.</li>
  <li>The account has not previously received a refund under this guarantee.</li>
</ul>

<h2>2. Pro-Rata Refunds for Cancellations</h2>
<p>For monthly or annual services cancelled mid-cycle:</p>
<ul>
  <li><strong>Annual plans:</strong> A pro-rata refund is available for any complete unused months, minus a 10% administration fee.</li>
  <li><strong>Monthly plans:</strong> No refund is issued for the current billing month; cancellation takes effect at the end of the paid period.</li>
  <li><strong>Hourly/on-demand resources:</strong> Billed to the minute; no refund for consumed resources.</li>
</ul>

<h2>3. Non-Refundable Items</h2>
<p>The following are not eligible for refund under any circumstances:</p>
<ul>
  <li>Domain registration and renewal fees (domains are registered immediately with the registry).</li>
  <li>SSL certificate fees once the certificate has been issued.</li>
  <li>Dedicated server setup fees.</li>
  <li>Accounts suspended or terminated for violation of our <a href="/acceptable-use-policy">Acceptable Use Policy</a> or <a href="/terms-of-service">Terms of Service</a>.</li>
  <li>Services that have been used for more than 7 days.</li>
  <li>Add-on services (IP addresses, backup storage, control panel licences) once provisioned.</li>
</ul>

<h2>4. Payment Method</h2>
<p>Refunds are issued to the original payment method where technically possible. If the original payment method is no longer available, we will issue account credit or arrange a bank transfer in Nigerian Naira (NGN) at the prevailing exchange rate on the refund date.</p>
<p>Processing time: 5&ndash;10 business days for card refunds; 1&ndash;3 business days for account credit.</p>

<h2>5. Service Credits &amp; Downtime</h2>
<p>Downtime beyond the SLA threshold is compensated with service credits as described in our <a href="/service-level-agreement">Service Level Agreement</a>. Credits are applied to your account balance and cannot be exchanged for cash.</p>

<h2>6. How to Request a Refund</h2>
<ol>
  <li>Log in to your dashboard at <a href="https://my.kloud101.com">my.kloud101.com</a>.</li>
  <li>Open a support ticket under <strong>Billing &rsaquo; Refund Request</strong>.</li>
  <li>Include your invoice number and reason for the request.</li>
  <li>Our billing team will respond within 1 business day.</li>
</ol>

<h2>7. Disputes</h2>
<p>If you believe a charge is incorrect, please contact our billing team before initiating a chargeback with your bank. Chargebacks without prior notification may result in account suspension and a chargeback fee.</p>

<h2>Contact</h2>
<p>Billing enquiries: <a href="mailto:billing@kloud101.com">billing@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'service-level-agreement',
                'title'          => 'Service Level Agreement',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Service Level Agreement</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>This Service Level Agreement (&ldquo;SLA&rdquo;) describes the uptime commitments and remedies for services provided by Kloud101 Limited.</p>

<h2>1. Uptime Commitment</h2>
<table>
  <thead><tr><th>Service</th><th>Monthly Uptime Target</th></tr></thead>
  <tbody>
    <tr><td>Virtual Private Servers (VPS)</td><td>99.9%</td></tr>
    <tr><td>Quick Servers</td><td>99.9%</td></tr>
    <tr><td>Dedicated Servers</td><td>99.9%</td></tr>
    <tr><td>Network Infrastructure</td><td>99.99%</td></tr>
    <tr><td>Control Panel / Dashboard</td><td>99.5%</td></tr>
  </tbody>
</table>
<p><strong>Monthly Uptime Percentage</strong> = (Total minutes in month &minus; Downtime minutes) &divide; Total minutes in month &times; 100</p>

<h2>2. Exclusions</h2>
<p>The following are excluded from uptime calculations:</p>
<ul>
  <li>Scheduled maintenance (with at least 48 hours&rsquo; notice via the status page and email).</li>
  <li>Emergency maintenance required to protect network integrity or security.</li>
  <li>Downtime caused by your actions or misconfigurations.</li>
  <li>Force majeure events (natural disasters, acts of government, power grid failures beyond our control).</li>
  <li>Third-party provider outages (upstream ISPs, DNS registrars, payment processors).</li>
  <li>DDoS attacks exceeding 10&nbsp;Gbps directed at your server.</li>
</ul>

<h2>3. Service Credits</h2>
<table>
  <thead><tr><th>Monthly Uptime</th><th>Credit</th></tr></thead>
  <tbody>
    <tr><td>99.0% &ndash; 99.9%</td><td>5% of monthly fee</td></tr>
    <tr><td>95.0% &ndash; 98.9%</td><td>10% of monthly fee</td></tr>
    <tr><td>90.0% &ndash; 94.9%</td><td>25% of monthly fee</td></tr>
    <tr><td>Below 90%</td><td>50% of monthly fee</td></tr>
  </tbody>
</table>
<p>Credits are applied to the next invoice. They are not redeemable for cash. The maximum credit in any calendar month is 50% of the monthly fee for the affected service.</p>

<h2>4. Claiming a Credit</h2>
<ol>
  <li>Submit a support ticket within <strong>14 days</strong> of the incident.</li>
  <li>Include the affected service ID, dates, and times of downtime.</li>
  <li>Our team will verify against monitoring data and apply credit within 5 business days of approval.</li>
</ol>

<h2>5. Support Response Times</h2>
<table>
  <thead><tr><th>Priority</th><th>Definition</th><th>First Response</th></tr></thead>
  <tbody>
    <tr><td>Critical</td><td>Complete service outage</td><td>1 hour</td></tr>
    <tr><td>High</td><td>Significant degradation</td><td>4 hours</td></tr>
    <tr><td>Medium</td><td>Partial functionality loss</td><td>8 hours</td></tr>
    <tr><td>Low</td><td>General enquiry or feature request</td><td>24 hours</td></tr>
  </tbody>
</table>

<h2>6. Monitoring &amp; Status</h2>
<p>Real-time status and historical incident reports are published at <a href="https://status.kloud101.com" target="_blank" rel="noopener">status.kloud101.com</a>. We recommend subscribing to status updates for your services.</p>

<h2>7. Sole Remedy</h2>
<p>Service credits described in this SLA are your sole and exclusive remedy for any failure by Kloud101 to meet the uptime commitments stated above.</p>

<h2>Contact</h2>
<p>SLA claims: <a href="mailto:support@kloud101.com">support@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'abuse-policy',
                'title'          => 'Abuse Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>Abuse Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>Kloud101 Limited takes abuse seriously. This policy describes how we handle abuse reports and what actions we take against accounts that violate our <a href="/acceptable-use-policy">Acceptable Use Policy</a> (AUP).</p>

<h2>1. Types of Abuse We Address</h2>
<ul>
  <li><strong>Network abuse:</strong> DDoS attacks, port scanning, IP spoofing, botnet activity.</li>
  <li><strong>Email abuse:</strong> spam campaigns, phishing emails, malware distribution via email.</li>
  <li><strong>Content abuse:</strong> child sexual abuse material, terrorism content, illegal content under Nigerian law or applicable international law.</li>
  <li><strong>Resource abuse:</strong> cryptocurrency mining beyond licensed allocation, scraping, account sharing.</li>
  <li><strong>Phishing &amp; fraud:</strong> websites impersonating banks, payment processors, or other organisations.</li>
  <li><strong>Copyright infringement:</strong> hosting pirated software, films, music, or other copyrighted works without authorisation.</li>
</ul>

<h2>2. How to Report Abuse</h2>
<p>To report abuse of our infrastructure:</p>
<ul>
  <li>Email <a href="mailto:abuse@kloud101.com">abuse@kloud101.com</a> with the subject line: <strong>Abuse Report &mdash; [Type]</strong>.</li>
  <li>Include the offending IP address or URL, timestamps (with timezone), log excerpts, and a description of the abuse.</li>
  <li>For CSAM, also report to the Internet Watch Foundation (IWF) at <a href="https://www.iwf.org.uk" target="_blank" rel="noopener">iwf.org.uk</a> and the National Center for Missing &amp; Exploited Children (NCMEC).</li>
</ul>

<h2>3. Our Response Process</h2>
<table>
  <thead><tr><th>Severity</th><th>Examples</th><th>Response Time</th></tr></thead>
  <tbody>
    <tr><td>Critical</td><td>CSAM, active DDoS source, ransomware C&amp;C</td><td>Within 2 hours &mdash; immediate suspension</td></tr>
    <tr><td>High</td><td>Phishing, spam campaigns, active malware host</td><td>Within 4 hours</td></tr>
    <tr><td>Medium</td><td>Copyright infringement, resource abuse</td><td>Within 24 hours</td></tr>
    <tr><td>Low</td><td>Policy queries, unverified reports</td><td>Within 48 hours</td></tr>
  </tbody>
</table>

<h2>4. Actions We May Take</h2>
<ul>
  <li><strong>Warning:</strong> for first-time, low-severity violations where the customer appears to be acting in good faith.</li>
  <li><strong>Content removal:</strong> we may remove specific files or URLs without suspending the entire account.</li>
  <li><strong>Null routing:</strong> traffic to or from abusive IP addresses may be null-routed to protect our network.</li>
  <li><strong>Account suspension:</strong> temporary suspension pending investigation.</li>
  <li><strong>Account termination:</strong> permanent termination for serious or repeated violations, without refund.</li>
  <li><strong>Law enforcement referral:</strong> we will cooperate with law enforcement for criminal activity.</li>
</ul>

<h2>5. Customer Notification</h2>
<p>Where legally permissible, we notify the account holder of an abuse investigation and provide a reasonable opportunity to respond before taking action, except where:</p>
<ul>
  <li>Immediate action is required to protect our network or other users.</li>
  <li>The content is illegal (e.g., CSAM) — in which case we act immediately without prior notice.</li>
  <li>A court order or law enforcement directive prohibits notification.</li>
</ul>

<h2>6. Appeal Process</h2>
<p>If you believe your account was suspended in error, email <a href="mailto:abuse@kloud101.com">abuse@kloud101.com</a> with the subject <strong>Abuse Appeal &mdash; [Account ID]</strong>. Appeals are reviewed within 2 business days by a member of our trust and safety team who was not involved in the original decision.</p>

<h2>Contact</h2>
<p>Abuse: <a href="mailto:abuse@kloud101.com">abuse@kloud101.com</a><br>Law enforcement requests: <a href="mailto:legal@kloud101.com">legal@kloud101.com</a></p>
HTML,
            ],

            [
                'slug'           => 'dmca-policy',
                'title'          => 'DMCA Policy',
                'version'        => '1.0',
                'effective_date' => '2025-01-01',
                'content'        => <<<'HTML'
<h1>DMCA Policy</h1>
<p>Last updated: 1 January 2025 &middot; Version 1.0</p>
<p>Kloud101 Limited respects intellectual property rights. This policy describes our process for handling Digital Millennium Copyright Act (DMCA) takedown notices and counter-notices. Although Kloud101 operates primarily under Nigerian law, we follow DMCA procedures as an international best practice for copyright complaints.</p>

<h2>1. Submitting a Takedown Notice</h2>
<p>If you are a copyright holder or authorised agent and believe content hosted on our infrastructure infringes your copyright, submit a written notice to our designated DMCA agent that includes <strong>all</strong> of the following:</p>
<ol>
  <li>Your physical or electronic signature (or the signature of the person authorised to act on behalf of the copyright owner).</li>
  <li>Identification of the copyrighted work claimed to have been infringed.</li>
  <li>Identification of the material that is claimed to be infringing, including the URL or IP address where it is hosted.</li>
  <li>Your contact information (name, address, telephone number, and email address).</li>
  <li>A statement that you have a good-faith belief that the use of the material is not authorised by the copyright owner, its agent, or the law.</li>
  <li>A statement, under penalty of perjury, that the information in the notice is accurate and that you are the copyright owner or are authorised to act on the owner&rsquo;s behalf.</li>
</ol>
<p><strong>DMCA Agent:</strong> Legal &amp; Compliance Team<br>
Email: <a href="mailto:dmca@kloud101.com">dmca@kloud101.com</a><br>
Kloud101 Limited, Lagos, Nigeria</p>

<h2>2. Our Response to Valid Notices</h2>
<p>Upon receiving a valid takedown notice, we will:</p>
<ul>
  <li>Acknowledge receipt within 1 business day.</li>
  <li>Notify the affected customer of the complaint where legally permissible.</li>
  <li>Remove or disable access to the allegedly infringing content within 3 business days of confirming the notice is complete and valid.</li>
  <li>Provide the customer with information about submitting a counter-notice.</li>
</ul>

<h2>3. Submitting a Counter-Notice</h2>
<p>If you believe content was removed as a result of mistake or misidentification, you may submit a counter-notice that includes:</p>
<ol>
  <li>Your physical or electronic signature.</li>
  <li>Identification of the material that has been removed and its previous location.</li>
  <li>A statement under penalty of perjury that you have a good-faith belief the content was removed as a result of mistake or misidentification.</li>
  <li>Your name, address, and telephone number, and a statement that you consent to the jurisdiction of the Federal Courts of Nigeria and will accept service of process from the complainant or their agent.</li>
</ol>
<p>Send counter-notices to <a href="mailto:dmca@kloud101.com">dmca@kloud101.com</a>. If we receive a valid counter-notice we will restore the removed content within 10&ndash;14 business days unless the original complainant notifies us they have filed a court action.</p>

<h2>4. Repeat Infringers</h2>
<p>Kloud101 maintains a repeat-infringer policy. Accounts that are the subject of three or more valid DMCA takedown notices within a 12-month period may be terminated without refund.</p>

<h2>5. Misrepresentation</h2>
<p>Knowingly submitting a materially false DMCA takedown notice or counter-notice may expose you to liability for damages, court costs, and attorney&rsquo;s fees under applicable law.</p>

<h2>6. Other Intellectual Property</h2>
<p>For trademark or other intellectual property concerns that are outside the scope of this DMCA policy, contact <a href="mailto:legal@kloud101.com">legal@kloud101.com</a>.</p>

<h2>Contact</h2>
<p>DMCA notices &amp; counter-notices: <a href="mailto:dmca@kloud101.com">dmca@kloud101.com</a></p>
HTML,
            ],
        ];

        foreach ($docs as $doc) {
            LegalDocument::updateOrCreate(
                ['slug' => $doc['slug']],
                $doc
            );
        }
    }
}
