import { MapPinIcon, PhoneIcon } from "lucide-react";
import { Separator } from "../../../../components/ui/separator";

const contactInfo = [
  {
    icon: "location",
    text: "ABC Company, 123 East, 17th Street, St. louis 10001",
  },
];

const phoneNumbers = [
  {
    icon: "phone",
    text: "(123) 456-7890",
  },
  {
    icon: "phone",
    text: "(123) 456-7890",
  },
];

const policyLinks = [
  { text: "Legal Notice" },
  { text: "Privacy Policy" },
  { text: "Refund Policy" },
];

export const FooterSection = (): JSX.Element => {
  return (
    <footer className="relative w-full flex justify-center bg-[url(/shapes-containerr.svg)] bg-[100%_100%] py-8">
      <div className="w-full max-w-[1081px] px-4 flex flex-col gap-8">
        <div className="flex flex-col gap-8">
          <Separator className="bg-[#e64f52] h-1" />

          <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <div className="flex-shrink-0">
              <img
                className="w-[477px] h-auto max-w-full object-contain"
                alt="Logo"
              />
            </div>

            <div className="flex flex-col gap-4">
              {contactInfo.map((info, index) => (
                <div
                  key={`contact-${index}`}
                  className="flex items-start gap-4"
                >
                  <MapPinIcon className="w-6 h-6 flex-shrink-0 text-dark" />
                  <p className="[font-family:'DM_Sans',Helvetica] font-normal text-dark text-sm leading-[22px]">
                    {info.text}
                  </p>
                </div>
              ))}

              <div className="flex flex-wrap gap-8">
                {phoneNumbers.map((phone, index) => (
                  <div
                    key={`phone-${index}`}
                    className="flex items-center gap-4"
                  >
                    <PhoneIcon className="w-6 h-6 flex-shrink-0 text-dark" />
                    <p className="[font-family:'Assistant',Helvetica] font-normal text-dark text-sm">
                      {phone.text}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        <div className="w-full -mx-4 px-4 py-4 bg-[#ffe2e2]">
          <div className="max-w-[1296px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <p className="font-body-extra-large-body-xl-regular font-[number:var(--body-extra-large-body-xl-regular-font-weight)] text-[#465d7c] text-[length:var(--body-extra-large-body-xl-regular-font-size)] tracking-[var(--body-extra-large-body-xl-regular-letter-spacing)] leading-[var(--body-extra-large-body-xl-regular-line-height)] [font-style:var(--body-extra-large-body-xl-regular-font-style)] whitespace-nowrap">
              Copyright © 2025. All Rights Reserved, Doccure
            </p>

            <div className="flex items-center justify-center gap-2 flex-wrap">
              {policyLinks.map((link, index) => (
                <div
                  key={`policy-${index}`}
                  className="flex items-center gap-2"
                >
                  {index > 0 && (
                    <div className="w-[5.14px] h-[5.14px] bg-[#0e82fd] rounded-[2.57px]" />
                  )}
                  <button className="font-body-extra-large-body-xl-regular font-[number:var(--body-extra-large-body-xl-regular-font-weight)] text-[#465d7c] text-[length:var(--body-extra-large-body-xl-regular-font-size)] tracking-[var(--body-extra-large-body-xl-regular-letter-spacing)] leading-[var(--body-extra-large-body-xl-regular-line-height)] [font-style:var(--body-extra-large-body-xl-regular-font-style)] whitespace-nowrap hover:underline">
                    {link.text}
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};
