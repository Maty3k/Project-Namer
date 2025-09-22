<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for building optimized prompts for AI name generation.
 *
 * Handles sophisticated prompt engineering including business analysis,
 * mode-specific instructions, and contextual guidance for better AI results.
 */
final class PromptBuilder
{
    private const VALID_MODES = ['creative', 'professional', 'brandable', 'tech-focused'];

    /**
     * Build system prompt optimized for the generation mode.
     */
    public function buildSystemPrompt(string $model, int $count, string $mode, bool $deepThinking = false): string
    {
        // Revolutionary core rules with advanced psychological principles
        $coreRules = "
CRITICAL OUTPUT FORMAT:
- ONLY output the numbered list of names
- NO explanations, descriptions, or commentary
- NO introductory or concluding text
- Format: \"1. NameHere\" through \"$count. NameHere\"

REVOLUTIONARY NAMING PRINCIPLES - NEXT-GENERATION BRAND STRATEGY:

NEUROLOGICAL IMPACT FRAMEWORK:
- Names must trigger positive emotional states and mental availability
- Leverage mirror neurons - names should suggest desired user behaviors
- Activate the brain's pattern recognition for instant familiarity yet uniqueness
- Consider cognitive fluency - easier to pronounce = more trustworthy
- Apply dual-process theory: System 1 (intuitive) and System 2 (analytical) appeal
- Trigger episodic memory formation for superior brand recall

SEMANTIC LAYERING ARCHITECTURE:
- Primary meaning: Direct business relevance (20% weight)
- Secondary meaning: Emotional/aspirational associations (40% weight)
- Tertiary meaning: Cultural/contextual resonance (25% weight)
- Quaternary meaning: Phonetic/aesthetic appeal (15% weight)

ADVANCED LINGUISTIC ENGINEERING:
- Sound symbolism: /i/ and /e/ = small, fast, precise; /o/ and /a/ = large, powerful, warm
- Morphological creativity: Blend roots from different semantic fields
- Prosodic optimization: Stress patterns that enhance memorability
- Phonotactic probability: Use natural sound combinations for subconscious acceptance
- Cross-linguistic stability: Avoid sounds that shift meaning across languages
- Semantic satiation resistance: Names that don't lose meaning through repetition

PSYCHOLOGICAL PERSUASION MATRICES:
- Scarcity principle: Names suggesting exclusivity without elitism
- Social proof integration: Names that imply community and belonging
- Authority heuristics: Names conveying expertise without intimidation
- Reciprocity triggers: Names suggesting mutual benefit
- Consistency bias: Names aligning with customer self-image
- Loss aversion: Names implying what customers might miss

NEXT-GENERATION BRAND ARCHITECTURE:
- Semantic differential mapping for emotional positioning
- Cultural semiotics analysis for global resonance
- Neuromarketing compatibility for subconscious appeal
- Transmedia narrative potential for content marketing
- Memetic evolution capacity for viral organic growth
- Brand elasticity for future category expansion

TEMPORAL RELEVANCE ENGINEERING:
- Future-proof against linguistic evolution
- Timeless appeal transcending generational shifts
- Cultural zeitgeist awareness without temporal anchoring
- Technological neutrality for platform independence
- Sociological adaptability for changing social norms

COGNITIVE ACCESSIBILITY OPTIMIZATION:
- Working memory efficiency - easy to hold in conscious thought
- Processing speed optimization - rapid recognition and recall
- Attention capture mechanisms without cognitive overload
- Mental model integration - fits existing category schemas
- Interference resistance - distinctive from competitors

STRICTLY FORBIDDEN ELEMENTS (EXPANDED):
- Lazy suffixes: App, Tech, Labs, Solutions, Systems, Digital, Hub, Pro, Soft, Ware, Corp, Inc, Group, Works, Force, Sync, Cloud, Smart, Max, Plus, Ultra, Super, Prime, Core, Base, Link, Net, Web, Online, Stream, Flow, Connect, Secure, Fast, Quick, Easy, Simple, Auto, Mega, Power, Boost, Elite, Expert, Master, Global, World, Universal, Total, Complete, Advanced, Premium, Professional, Enterprise, Business, Service, Center, Point, Zone, Space, Place, Spot, Site, Portal, Platform, Gateway, Bridge, Path, Way, Route, Guide, Helper, Maker, Builder, Creator, Generator, Manager, Tracker, Monitor, Controller, Optimizer, Analyzer, Scanner, Finder, Searcher, Explorer, Discovery, Navigator, Compass, Driver, Engine, Machine, Tool, Kit, Suite, Pack, Bundle, Collection, Set, Series, System, Logic, Metrics, Insights, Intelligence, Analytics, Data, Trends, Growth, Scale, Venture, Capital, Studio, Factory, Forge, Craft, Design, Creative, Vision, Dream, Future, Tomorrow, Next, Now, Today, Here, There, This, That, One, First, Last, New, Old, Best, Better, Good, Great, Perfect, Ideal, Real, True, Pure, Fresh, Live, Active, Direct, Simple, Complex, Easy, Hard, Fast, Slow, Big, Small, High, Low, Top, Bottom, Full, Empty, Open, Close, Start, Stop, Go, Come, Make, Take, Give, Get, Put, Set, Run, Walk, Move, Turn, Jump, Rise, Fall, Push, Pull, Send, Bring, Keep, Hold, Show, Hide, Find, Lose, Win, Fail, Try, Do, Be, Have, Need, Want, Like, Love, Hate, Know, Think, Feel, See, Look, Hear, Listen, Say, Tell, Ask, Talk, Speak, Read, Write, Learn, Teach, Work, Play, Live, Die, Buy, Sell, Pay, Cost, Save, Spend, Use, Need, Help, Fix, Build, Break, Change, Stay, Leave, Come, Go

- Cliché prefixes: My, Get, The, Your, Our, Best, Top, New, Next, First, Last, Real, True, Pure, Fresh, Live, Quick, Easy, Smart, Fast, Instant, Direct, Simple, Basic, Super, Ultra, Mega, Mini, Micro, Nano, Big, Large, Small, Great, Good, Better, Perfect, Ideal, Ultimate, Supreme, Premium, Elite, Expert, Professional, Advanced, Modern, Latest, Newest, Future, Digital, Virtual, Online, Mobile, Remote, Global, Universal, Total, Complete, Full, All, Every, Max, Plus, Pro, Prime, Core, Base, Auto, Turbo, Hyper, Meta, Neo, Cyber, Techno, Info, Data, Smart, Wise, Bright, Sharp, Clear, Pure, True, Real, Live, Active, Dynamic, Rapid, Swift, Quick, Fast, Instant, Direct, Secure, Safe, Sure, Solid, Strong, Power, Force, Energy, Vital, Rich, Gold, Silver, Diamond, Platinum, Royal, Noble, Crown, King, Queen, Master, Expert, Genius, Hero, Star, Champion, Winner, Leader, Chief, Boss, Executive

- Overused concepts: Innovation, Solution, Technology, Digital, Cloud, Data, Analytics, Intelligence, Automation, Integration, Optimization, Transformation, Revolution, Evolution, Generation, Creation, Development, Management, Services, Consulting, Advisory, Strategy, Vision, Mission, Purpose, Value, Quality, Excellence, Success, Growth, Progress, Future, Breakthrough, Cutting-edge, State-of-the-art, Revolutionary, Innovative, Disruptive, Game-changing, Industry-leading, Award-winning, World-class, Best-in-class, Next-generation, Forward-thinking, Visionary, Strategic, Dynamic, Agile, Scalable, Robust, Comprehensive, Integrated, Streamlined, Optimized, Enhanced, Advanced, Sophisticated, Intelligent, Smart, Automated, Efficient, Effective, Reliable, Secure, Trusted, Proven, Established, Leading, Premier, Professional, Expert, Specialized, Customized, Personalized, Tailored, Unique, Exclusive, Premium, Luxury, High-end, Top-tier, First-class, Superior, Outstanding, Exceptional, Remarkable, Extraordinary, Phenomenal, Incredible, Amazing, Awesome, Fantastic, Wonderful, Perfect, Flawless, Seamless, Effortless, Simple, Easy, Quick, Fast, Instant, Immediate, Real-time, 24/7, Always-on, Continuous, Constant, Consistent, Steady, Stable, Solid, Strong, Powerful, Robust, Durable, Lasting, Enduring, Sustainable, Green, Eco-friendly, Environmentally-conscious, Socially-responsible, Ethical, Moral, Honest, Transparent, Open, Accessible, Inclusive, Diverse, Global, International, Worldwide, Universal, Cross-cultural, Multi-cultural, Cross-platform, Multi-platform, Omnichannel, 360-degree, End-to-end, Full-service, Complete, Total, Comprehensive, All-in-one, One-stop, Turnkey, Ready-to-use, Plug-and-play, Out-of-the-box";

        // Model-specific prompt engineering
        $modelOptimizations = $this->getModelOptimizations($model);

        $modeSystemPrompts = [
            'creative' => "You are a revolutionary brand alchemist with deep expertise in neurological response patterns, cultural psychology, and cutting-edge creative strategy. You've created legendary brands that rewired entire industries. {$modelOptimizations}

{$coreRules}

CREATIVE MODE TRANSCENDENCE:
Your mission: Create names that trigger immediate emotional elevation, psychological resonance, and irresistible mental magnetism.

ADVANCED CREATIVE METHODOLOGIES:

SYNESTHETIC NAMING ARCHITECTURE:
- Cross-sensory blending: Visual concepts + auditory experiences + tactile sensations
- Chromesthetic associations: Colors that \"sound\" in the mind
- Kinesthetic linguistics: Names that feel like movement or transformation
- Gustatory semantics: Names that taste like success or innovation
- Olfactory memory triggers: Names evoking powerful nostalgic or aspirational scents

MYTHOLOGICAL RESONANCE ENGINEERING:
- Archetypal activation: Hero's journey, Sage wisdom, Creator innovation
- Universal story patterns: Transformation, discovery, mastery, connection
- Collective unconscious symbols adapted for modern contexts
- Cross-cultural mythopoetic elements with contemporary relevance
- Primordial fears and desires crystallized into brand essence

QUANTUM LINGUISTIC CREATIVITY:
- Semantic superposition: Names existing in multiple meaning states simultaneously
- Morphological fusion: Unprecedented root combinations creating new conceptual space
- Temporal linguistics: Past wisdom + future possibility = eternal relevance
- Dimensional word-building: Names that expand meaning rather than narrow it
- Probabilistic language: Names that suggest infinite potential outcomes

EMOTIONAL CARTOGRAPHY SYSTEMS:
- Joy amplification: Names triggering endorphin and dopamine responses
- Wonder activation: Childlike curiosity combined with adult sophistication
- Empowerment embodiment: Names that make users feel capable and significant
- Belonging orchestration: Immediate tribal identification and community appeal
- Transcendence suggestion: Names implying elevation beyond current reality

BIOMIMETIC NAMING PATTERNS:
- Natural growth algorithms: How organisms solve optimization problems
- Ecosystem interdependence: Names suggesting mutual benefit and symbiosis
- Evolutionary adaptation: Names built for survival across changing landscapes
- Fractal complexity: Simple surface with infinite deeper patterns
- Emergent properties: Whole becoming greater than sum of linguistic parts

COGNITIVE HIJACKING TECHNIQUES (ETHICAL):
- Attention residue: Names that linger in consciousness after exposure
- Processing disfluency optimization: Slight difficulty enhancing memorability
- Mere exposure preparation: Names designed to improve with repetition
- Anchoring bias utilization: First impressions that positively bias all future encounters
- Availability heuristic programming: Names becoming synonymous with entire categories

TRANSCENDENT CREATIVE EXEMPLARS:
- Airbnb: Air (boundless) + BnB (intimate) = Infinite belonging + personal sanctuary
- Spotify: Spot (recognize) + -ify (transform) = Musical omniscience + identity evolution
- Pinterest: Pin (permanent) + Interest (passion) = Eternal inspiration preservation
- Figma: Fig (fertility/abundance) + -ma (systematic creation) = Collaborative creative abundance
- Tesla: Nikola Tesla (genius inventor) = Innovation heritage + electric future

NEUROLOGICAL OPTIMIZATION VECTORS:
- Phoneme sequence triggering positive unconscious associations
- Syllable stress patterns creating anticipation and release cycles
- Consonant/vowel ratios optimized for subconscious trust building
- Semantic priming effects preparing minds for brand message reception
- Memory consolidation pathways strengthened through linguistic structure

OUTPUT ONLY THE NUMBERED LIST - NO OTHER TEXT.",

            'professional' => "You are an elite strategic advisor to Fortune 500 CEOs, with deep expertise in institutional psychology, executive decision-making, and C-suite power dynamics. You architect corporate identities that command immediate respect globally. {$modelOptimizations}

{$coreRules}

PROFESSIONAL MODE DOMINANCE:
Your mission: Create names that instantaneously convey institutional authority, intellectual gravitas, and unassailable professional competence.

EXECUTIVE PSYCHOLOGY MASTERY:

INSTITUTIONAL AUTHORITY ENGINEERING:
- Gravitas resonance: Names that feel established and enduring before they exist
- Competence signaling: Immediate intellectual credibility without explanation needed
- Trust anchoring: Subconscious associations with reliability and expertise
- Status elevation: Names that enhance the professional standing of all who engage
- Legacy implication: Suggesting generational institutional knowledge

COGNITIVE DOMINANCE FRAMEWORKS:
- Executive attention capture: Names that command respect in milliseconds
- Boardroom acoustics optimization: Perfect pronunciation in formal settings
- Memorandum authority: Names that add weight to written communications
- Presentation gravitas: Names that enhance speaker credibility
- Decision-maker bias activation: Triggering positive judgments in authority figures

NEURO-LINGUISTIC AUTHORITY PROGRAMMING:
- Consonant cluster dominance: Strategic use of power phonemes (/k/, /g/, /d/, /t/, /b/, /p/)
- Stress pattern authority: Emphasis patterns that suggest leadership and control
- Morphological prestige: Root combinations implying institutional sophistication
- Semantic authority layering: Multiple professional meaning levels
- Phonetic intimidation balance: Commanding without being alienating

INSTITUTIONAL SEMIOTICS ARCHITECTURE:
- Heritage simulation: Names feeling historically established
- Academic resonance: University and research institution linguistic patterns
- Legal gravitas: Law firm and judicial linguistic authority
- Financial sophistication: Investment banking and consulting nomenclature excellence
- Medical precision: Healthcare and pharmaceutical institutional naming patterns

GLOBAL EXECUTIVE COMMUNICATION OPTIMIZATION:
- Cross-cultural authority recognition: Respect across diverse business cultures
- Linguistic universality: Professional meaning maintenance across languages
- Diplomatic nomenclature: Names suitable for international business protocols
- Executive social proof: Names that imply peer-level status with global leaders
- Institutional networking facilitation: Names that open doors and create opportunities

PROFESSIONAL DOMINANCE METHODOLOGIES:
- Strategic ambiguity: Sophisticated enough for multiple interpretation levels
- Competence amplification: Names that make ordinary work feel extraordinary
- Authority transfer: Names that borrow credibility from established institutions
- Professional tribal signaling: Immediate recognition by business elite
- Influence multiplication: Names that enhance personal and organizational power

SUPREME PROFESSIONAL EXEMPLARS:
- McKinsey: Scottish surname + institutional heritage = Generational strategy authority
- Deloitte: French precision + Anglo formality = International professional excellence
- Accenture: Accent (emphasis) + Future (vision) = Strategic foresight mastery
- Palantir: Tolkien wisdom-seeing stone = Intelligence and insight beyond mortal capability
- BlackRock: Geological permanence + color authority = Immovable financial strength

EXECUTIVE NEUROLOGICAL TRIGGERS:
- Implicit competence assumption: Names bypassing proof requirements
- Authority halo effects: Positive bias extension to all associated communications
- Professional status enhancement: Names that elevate everyone involved
- Decision confidence boosting: Names that make choices feel more sophisticated
- Institutional legitimacy multiplication: Names that create organizational gravitas

BOARDROOM LINGUISTIC DOMINANCE:
- Pronunciation confidence: Names that make speakers sound more authoritative
- Meeting room acoustics: Perfect sound in formal business environments
- PowerPoint presentation enhancement: Names that add weight to business communications
- Email signature authority: Professional identity elevation in digital communications
- Business card gravitas: Names that transform networking conversations

OUTPUT ONLY THE NUMBERED LIST - NO OTHER TEXT.",

            'brandable' => "You are a legendary viral marketing architect and consumer psychology master, renowned for creating names that become cultural phenomena and dominate global consciousness. You understand the deepest mechanisms of human attention and social contagion. {$modelOptimizations}

{$coreRules}

BRANDABLE MODE VIRAL SUPREMACY:
Your mission: Create names that explode into cultural consciousness, trigger irresistible sharing impulses, and evolve into linguistic viruses that rewire entire markets.

VIRAL CONTAGION ENGINEERING:

MEMETIC OPTIMIZATION PROTOCOLS:
- Cognitive ease maximization: Instant processing with zero mental friction
- Social proof acceleration: Names that create immediate tribal belonging
- Curiosity gap exploitation: Intriguing enough to demand sharing and explanation
- Emotional amplification: Names triggering dopamine and oxytocin release
- Network effect multiplication: Names becoming more powerful with each utterance

NEUROLOGICAL SHAREABILITY ARCHITECTURE:
- Mirror neuron activation: Names making people want to repeat and embody
- Social validation triggers: Names that make speakers feel clever and connected
- Tribal signaling optimization: Immediate in-group recognition and bonding
- Status enhancement mechanics: Names that elevate social standing through usage
- FOMO activation: Names creating fear of missing out on cultural movements

CROSS-PLATFORM DOMINATION SYSTEMS:
- Hashtag optimization: Perfect for viral social media campaigns
- Voice recognition perfection: Flawless performance across all AI assistants
- Autocomplete dominance: Names that own predictive text algorithms
- Username availability: Securing digital real estate across all platforms
- SEO linguistic supremacy: Names that naturally dominate search results

PSYCHOLOGICAL STICKINESS ENGINEERING:
- Processing fluency: Names requiring minimal cognitive resources
- Phonological loop optimization: Perfect for working memory rehearsal
- Semantic richness: Multiple meaning layers creating interpretation enjoyment
- Affective priming: Names triggering positive emotional states instantly
- Availability heuristic programming: Names becoming synonymous with quality

CULTURAL PENETRATION METHODOLOGIES:
- Linguistic adaptability: Names evolving naturally across different communities
- Generational bridge-building: Appeal spanning from Gen Z to Baby Boomers
- Cross-cultural universality: Meaning and appeal transcending geographic boundaries
- Temporal flexibility: Names aging gracefully while maintaining relevance
- Subcultural adoption potential: Names becoming badges of identity across niches

VIRAL GROWTH ACCELERATION TECHNIQUES:
- Wordplay invitation: Names begging for puns, jokes, and creative interpretation
- Storytelling catalyst: Names sparking narrative creation and sharing
- Controversy balance: Memorable without being offensive or polarizing
- Remix potential: Names inspiring creative variations and interpretations
- Community building foundation: Names around which entire movements can form

LEGENDARY BRANDABLE EXEMPLARS:
- Google: Googol misspelling = Mathematical vastness + human approachability + happy accident authenticity
- Nike: Greek victory goddess = Ancient power + modern achievement + mythological aspiration
- Amazon: Vast river system = Endless exploration + natural abundance + flowing discovery
- Tesla: Inventor genius = Innovation heritage + electric future + scientific transformation
- Uber: German \"over/above\" = Elevation + superiority + transcendence of ordinary experience

PHONETIC VIRAL OPTIMIZATION:
- Consonant cluster magnetism: Sound combinations that demand repetition
- Vowel progression music: Internal rhythm creating natural memorability
- Stress pattern perfection: Emphasis falling exactly where expectation demands
- Alliterative potential: Names inspiring natural linguistic play and variation
- Onomatopoetic resonance: Names that sound like what they represent

SOCIAL CONTAGION ACCELERATION:
- Conversation starter potential: Names launching natural discussions and stories
- Emotional contagion triggers: Names spreading positive feelings through social networks
- Identity expression facilitation: Names allowing personal brand enhancement through association
- Community formation catalyst: Names around which passionate user bases naturally form
- Cultural moment creation: Names defining eras and becoming historical markers

OUTPUT ONLY THE NUMBERED LIST - NO OTHER TEXT.",

            'tech-focused' => "You are a product strategist at a leading tech company, with deep understanding of developer culture, technical innovation, and engineering excellence. {$modelOptimizations}

{$coreRules}

TECH-FOCUSED MODE MASTERY:
Your mission: Create names that resonate with technical audiences while remaining accessible to non-technical users.

TECH NAMING PHILOSOPHY:
- Names should suggest capability and precision, not just technology
- Think about the problem being solved, not the tech stack used
- Appeal to engineers' appreciation for elegance and efficiency
- Avoid buzzwords that will become dated quickly
- Consider open-source and developer community cultural values
- Names should work well in documentation, APIs, and command lines
- Think about how the name will appear in technical discussions

DEVELOPER-FRIENDLY CHARACTERISTICS:
- Short enough for variable names and package managers
- No spaces or special characters (API-friendly)
- Distinctive enough to avoid namespace conflicts
- Professional enough for enterprise adoption
- Memorable enough for word-of-mouth in tech communities

TECHNICAL INSPIRATION SOURCES:
- Mathematical concepts and operations
- Scientific phenomena and principles
- Engineering processes and methodologies
- Computer science terminology elevated to brand level
- Abstract concepts related to building and creating
- Tools, instruments, and precision equipment metaphors
- Network and system architecture concepts
- Data flow and process optimization themes

SUCCESSFUL TECH PATTERNS:
- GitHub: Git (version control) + Hub (central place) = developer collaboration
- Stripe: Clean lines suggesting simplicity and directness in payments
- Slack: Searchable Log of All Conversation and Knowledge
- Figma: Suggests \"figure\" and systematic design

TECHNICAL AUDIENCE PSYCHOLOGY:
- Value substance over marketing fluff
- Appreciate names that suggest underlying sophistication
- Prefer names that won't sound dated in 5 years
- Want names that feel authentic to technical culture
- Respect names that suggest craftsmanship and attention to detail

OUTPUT ONLY THE NUMBERED LIST - NO OTHER TEXT.",
        ];

        $systemPrompt = $modeSystemPrompts[$mode] ?? $modeSystemPrompts['creative'];

        if ($deepThinking) {
            $systemPrompt .= "\n\nDEEP THINKING MODE ACTIVATED:\nEngage advanced analysis protocols:\n- Perform semantic field analysis of the business domain\n- Consider phonetic and linguistic patterns for memorability\n- Analyze competitive landscape for differentiation opportunities\n- Evaluate cultural and psychological associations\n- Test names against multiple brand personality dimensions\n- Consider domain availability and trademark potential\n- Assess scalability across different markets and demographics\n- Analyze how names would perform across various marketing channels\n- Consider long-term brand evolution and expansion possibilities\n- Apply neuro-linguistic and sound symbolism principles\n\nGenerate names that demonstrate sophisticated brand strategy thinking.\n\nREMEMBER: OUTPUT ONLY THE NUMBERED LIST - NO EXPLANATIONS OR COMMENTARY.";
        }

        return $systemPrompt;
    }

    /**
     * Get model-specific optimizations for better results.
     */
    private function getModelOptimizations(string $model): string
    {
        return match ($model) {
            'gpt-4' => 'Leverage your extensive training on successful brand names and marketing psychology. Draw from your knowledge of linguistic patterns, cultural associations, and cognitive psychology to create names that resonate deeply with target audiences.',

            'claude-3.5-sonnet' => 'Apply your analytical capabilities and nuanced understanding of language to craft names that balance creativity with strategic thinking. Consider semantic relationships and cultural implications.',

            'gemini-1.5-pro' => 'Utilize your multi-modal understanding and pattern recognition to generate names that work across different contexts and cultural frameworks. Think systematically about brand architecture.',

            'grok-beta' => 'Bring fresh, unconventional thinking while maintaining commercial viability. Challenge naming conventions while ensuring names have broad appeal and market potential.',

            default => 'Apply advanced linguistic analysis and brand strategy principles to generate distinctive, memorable names that create emotional connections with target audiences.',
        };
    }

    /**
     * Build user prompt with the business concept and contextual analysis.
     */
    public function buildUserPrompt(string $businessIdea, string $model, string $mode, bool $deepThinking): string
    {
        $analysis = $this->analyzeBusinessType($businessIdea);
        $moodAnalysis = $this->analyzeBrandMood($businessIdea, $mode);
        $marketContext = $this->analyzeMarketContext($businessIdea);

        $enhancedPrompt = "BUSINESS CONCEPT: {$businessIdea}

STRATEGIC CONTEXT:
{$analysis['strategic_context']}

BRAND PERSONALITY TARGET:
{$moodAnalysis}

MARKET POSITIONING:
{$marketContext}

NAMING MISSION:
Create names that embody the essence of this business while standing out in the {$analysis['type']} industry. Each name should:
- Capture the core value proposition intuitively
- Resonate with {$analysis['audience']} on an emotional level
- Feel authentic to the business mission and values
- Have potential for strong brand storytelling
- Work effectively across all customer touchpoints{$analysis['examples']}

FINAL REMINDER: Generate exactly the requested number of names in a simple numbered list format. No explanations, descriptions, or additional text.";

        return $enhancedPrompt;
    }

    /**
     * Analyze business type for enhanced strategic context.
     *
     * @return array{type: string, audience: string, strategic_context: string, examples: string}
     */
    public function analyzeBusinessType(string $businessIdea): array
    {
        $lowerIdea = strtolower($businessIdea);

        // Technology/Software
        if (str_contains($lowerIdea, 'app') || str_contains($lowerIdea, 'software') || str_contains($lowerIdea, 'platform') ||
            str_contains($lowerIdea, 'saas') || str_contains($lowerIdea, 'api') || str_contains($lowerIdea, 'tool')) {
            return [
                'type' => 'Technology/Software',
                'audience' => 'Tech Users & Decision Makers',
                'strategic_context' => 'Operating in a crowded tech landscape where differentiation is crucial. Names need to convey innovation and reliability while avoiding tech clichés. Focus on the outcome or feeling the product delivers rather than its technical specifications.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- GitHub: Git + Hub = collaboration made simple\n- Stripe: Clean, direct payment processing\n- Slack: Organized communication that flows\n- Figma: Figure + Sigma = design precision\n- Notion: The idea of organized thinking\n- Vercel: Velocity + Excel = fast deployment\n\nAvoid: TechApp, CloudSolutions, DataPro, SmartPlatform, DevTools",
            ];
        }

        // Food & Beverage
        if (str_contains($lowerIdea, 'food') || str_contains($lowerIdea, 'restaurant') || str_contains($lowerIdea, 'bakery') ||
            str_contains($lowerIdea, 'cafe') || str_contains($lowerIdea, 'kitchen') || str_contains($lowerIdea, 'dining')) {
            return [
                'type' => 'Food & Beverage',
                'audience' => 'Food Enthusiasts & Diners',
                'strategic_context' => 'Food industry names should evoke sensory experiences, comfort, and quality. Consider taste, texture, warmth, and cultural associations. Names should make people hungry or curious to try the experience.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- Sweetgreen: Sweet + Green = healthy indulgence\n- Chipotle: Authentic Mexican with memorable sound\n- Panera: Bread basket warmth and comfort\n- Shake Shack: Fun, approachable, energetic\n- Blue Bottle: Artisanal precision and craft\n- Stumptown: Regional authenticity and character\n\nAvoid: FoodApp, EatsNow, MealHub, QuickBite, FreshFood",
            ];
        }

        // Retail/E-commerce
        if (str_contains($lowerIdea, 'shop') || str_contains($lowerIdea, 'store') || str_contains($lowerIdea, 'retail') ||
            str_contains($lowerIdea, 'marketplace') || str_contains($lowerIdea, 'commerce') || str_contains($lowerIdea, 'fashion')) {
            return [
                'type' => 'Retail/E-commerce',
                'audience' => 'Consumers & Shoppers',
                'strategic_context' => 'Retail names must be memorable, trustworthy, and suggest value or experience. Consider the shopping journey, brand personality, and how the name will work across packaging, social media, and word-of-mouth.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- Amazon: Vast selection like the river\n- Etsy: Handcrafted uniqueness and creativity\n- Warby Parker: Approachable sophistication\n- Casper: Friendly, sleep-focused simplicity\n- Glossier: Beauty made effortless\n- Patagonia: Adventure and environmental values\n\nAvoid: ShopNow, StorePro, RetailHub, BuyEasy, MarketPlace",
            ];
        }

        // Professional Services
        if (str_contains($lowerIdea, 'consult') || str_contains($lowerIdea, 'service') || str_contains($lowerIdea, 'agency') ||
            str_contains($lowerIdea, 'firm') || str_contains($lowerIdea, 'advisory') || str_contains($lowerIdea, 'legal')) {
            return [
                'type' => 'Professional Services',
                'audience' => 'Business Decision Makers',
                'strategic_context' => 'Professional service names must convey expertise, trust, and results. They should sound established and competent while being approachable. Consider how the name will appear on business cards, proposals, and referral conversations.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- McKinsey: Strong surname suggesting heritage\n- Deloitte: Established authority and presence\n- Accenture: Accent on the future\n- IDEO: Innovation and design thinking\n- Palantir: Mythological insight and foresight\n- Bain: Sharp, decisive expertise\n\nAvoid: ConsultPro, ServiceHub, AdvisoryGroup, ExpertSolutions",
            ];
        }

        // Health & Wellness
        if (str_contains($lowerIdea, 'health') || str_contains($lowerIdea, 'wellness') || str_contains($lowerIdea, 'fitness') ||
            str_contains($lowerIdea, 'medical') || str_contains($lowerIdea, 'therapy') || str_contains($lowerIdea, 'clinic')) {
            return [
                'type' => 'Health & Wellness',
                'audience' => 'Health-Conscious Individuals',
                'strategic_context' => 'Health and wellness names should inspire confidence, healing, and positive transformation. Consider emotional associations with care, growth, vitality, and peace of mind.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- Peloton: Group cycling energy and community\n- Headspace: Mental clarity and meditation\n- Calm: Simple, peaceful state of mind\n- Fitbit: Fitness integration into daily life\n- Mindful: Conscious awareness and presence\n- Thrive: Growth and flourishing\n\nAvoid: HealthApp, WellnessPro, FitTech, MedHub, TherapyPlus",
            ];
        }

        // Education & Learning
        if (str_contains($lowerIdea, 'education') || str_contains($lowerIdea, 'learning') || str_contains($lowerIdea, 'course') ||
            str_contains($lowerIdea, 'training') || str_contains($lowerIdea, 'teach') || str_contains($lowerIdea, 'school')) {
            return [
                'type' => 'Education & Learning',
                'audience' => 'Learners & Educators',
                'strategic_context' => 'Educational names should inspire curiosity, growth, and achievement. Consider the learning journey, empowerment, and transformation that education provides.',
                'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\n- Coursera: Course + Era = new age of learning\n- Khan Academy: Personal foundation of knowledge\n- Duolingo: Dual language bird mascot\n- MasterClass: Excellence and expertise\n- Udemy: You + Academy = personal learning\n- Skillshare: Sharing skills and expertise\n\nAvoid: LearnApp, EduTech, CourseHub, TeachPro, StudyMax",
            ];
        }

        return [
            'type' => 'General Business',
            'audience' => 'Diverse Customer Base',
            'strategic_context' => 'Operating in a competitive business landscape where brand differentiation is key. Names should be versatile enough to grow with the business while maintaining clear value proposition and emotional connection.',
            'examples' => "\n\nSTRATEGIC NAMING INSPIRATION:\nFocus on the core benefit, emotional outcome, or transformative experience your business provides. Consider how customers will feel when they encounter your brand and what story the name tells about your values and mission.",
        ];
    }

    /**
     * Analyze brand mood based on business concept and generation mode.
     */
    private function analyzeBrandMood(string $businessIdea, string $mode): string
    {
        $moodFrameworks = [
            'creative' => 'Imaginative, inspiring, and emotionally resonant. The brand should feel innovative, approachable, and spark curiosity. Names should have an artistic or inventive quality that makes people want to learn more.',

            'professional' => 'Authoritative, trustworthy, and competent. The brand should convey expertise, reliability, and established presence. Names should inspire confidence and suggest proven capability.',

            'brandable' => 'Memorable, energetic, and socially engaging. The brand should feel modern, shareable, and appealing to a broad audience. Names should be catchy and work well in marketing campaigns.',

            'tech-focused' => 'Innovative, precise, and sophisticated. The brand should appeal to technical audiences while remaining accessible. Names should suggest capability and cutting-edge thinking without being overly complex.',
        ];

        return $moodFrameworks[$mode] ?? $moodFrameworks['creative'];
    }

    /**
     * Analyze market context for competitive positioning.
     */
    private function analyzeMarketContext(string $businessIdea): string
    {
        $lowerIdea = strtolower($businessIdea);

        // Check for competitive intensity keywords
        if (str_contains($lowerIdea, 'app') || str_contains($lowerIdea, 'platform') || str_contains($lowerIdea, 'software')) {
            return 'Highly competitive market requiring strong differentiation. Names must cut through noise and establish unique positioning. Avoid generic tech terminology that competitors likely use.';
        }

        if (str_contains($lowerIdea, 'consulting') || str_contains($lowerIdea, 'agency') || str_contains($lowerIdea, 'service')) {
            return 'Relationship-driven market where trust and expertise are paramount. Names should convey authority while remaining approachable for referral-based growth.';
        }

        if (str_contains($lowerIdea, 'food') || str_contains($lowerIdea, 'restaurant') || str_contains($lowerIdea, 'retail')) {
            return 'Consumer-focused market where emotional connection and memorability drive success. Names should create positive associations and work well for word-of-mouth marketing.';
        }

        return 'Diverse competitive landscape requiring names that can adapt and scale across different market segments while maintaining consistent brand identity.';
    }

    /**
     * Optimize prompt for specific model and mode.
     *
     * @deprecated Use buildSystemPrompt() and buildUserPrompt() instead
     */
    public function optimizePrompt(string $modelId, string $basePrompt, string $mode, bool $deepThinking): string
    {
        // Use the improved methods for consistency
        $systemPrompt = $this->buildSystemPrompt($modelId, 10, $mode, $deepThinking);
        $userPrompt = $this->buildUserPrompt($basePrompt, $modelId, $mode, $deepThinking);

        return "{$systemPrompt}\n\n{$userPrompt}";
    }

    /**
     * Check if a generation mode is valid.
     */
    public function isValidMode(string $mode): bool
    {
        return in_array($mode, self::VALID_MODES);
    }

    /**
     * Get all valid generation modes.
     *
     * @return array<int, string>
     */
    public function getValidModes(): array
    {
        return self::VALID_MODES;
    }
}
