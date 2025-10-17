import { registerBlockType } from '@wordpress/blocks';
import { TextControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { withSelect } from '@wordpress/data';

const EMDRSearchBlock = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [results, setResults] = useState([]);

    const handleSearch = async () => {
        const response = await fetch(`/wp-json/emdr/v1/search?term=${searchTerm}`);
        const data = await response.json();
        setResults(data);
    };

    return (
        <div className="emdr-search-block">
            <TextControl
                label="Search for EMDR Therapists"
                value={searchTerm}
                onChange={(value) => setSearchTerm(value)}
            />
            <Button isPrimary onClick={handleSearch}>
                Search
            </Button>
            <ul>
                {results.map((therapist) => (
                    <li key={therapist.id}>{therapist.name}</li>
                ))}
            </ul>
        </div>
    );
};

registerBlockType('emdr/therapist-finder', {
    title: 'EMDR Therapist Finder',
    icon: 'search',
    category: 'widgets',
    edit: EMDRSearchBlock,
    save: () => {
        return null; // Dynamic block, no save output
    },
});